<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\AuditLog;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Login gagal!'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        AuditLog::create([
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'action'      => 'LOGIN',
            'description' => "User " . $user->name . " masuk ke sistem",
            'ip_address'  => $request->ip()
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required',
            'company_id' => 'nullable|exists:companies,id',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048'
        ]);

        try {
            DB::beginTransaction();

            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public_uploads');
            }

            $coverPath = null;
            if ($request->hasFile('cover_image')) {
                $coverPath = $request->file('cover_image')->store('coverss', 'public_uploads');
            }

            // Simpan User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $request->role,
                'company_id' => $request->company_id, 
                'position' => $request->position,      
                'phone' => $request->phone,            
                'address' => $request->address,            
                'hourly_rate' => $request->hourly_rate,
                'avatar_url' => $avatarPath,
                'facebook' => $request->facebook,
                'twitter' => $request->twitter,
                'instagram' => $request->instagram,
                'linkedin' => $request->linkedin,
                'cover_url' => $request->cover_url, // Preset
                'cover_image' => $coverPath         // File Upload
            ]);

            // Simpan Bank jika diisi
            if ($request->bank_name && $request->account_number) {
                DB::table('finance_banks')->insert([
                    'user_id' => $user->id,
                    'bank_name' => $request->bank_name,
                    'account_name' => $request->account_name ?: $user->name,
                    'account_number' => $request->account_number,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            AuditLog::create([
                'user_id'     => $user->id,
                'user_name'   => $user->name,
                'action'      => 'REGISTER',
                'description' => "Mendaftarkan personel baru: " . $user->name,
                'ip_address'  => $request->ip()
            ]);

            DB::commit();
            return response()->json(['message' => 'Personel berhasil didaftarkan!', 'user' => $user], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal mendaftar: ' . $e->getMessage()], 500);
        }
    }   
    
    public function index(Request $request) 
    {
        try {
            $query = User::leftJoin('finance_banks', 'users.id', '=', 'finance_banks.user_id')
                        ->select(
                            'users.id', 'users.name', 'users.email', 'users.role', 'users.company_id', 
                            'users.position', 'users.phone', 'users.address', 'users.hourly_rate', 
                            'users.avatar_url', 'users.created_at',
                            'users.facebook', 'users.twitter', 'users.instagram', 'users.linkedin',
                            'users.cover_url', 'users.cover_image', // <--- Pastikan dua kolom ini ada
                            'finance_banks.bank_name', 'finance_banks.account_name', 'finance_banks.account_number'
                        )
                        ->orderBy('users.created_at', 'desc');

            if ($request->filled('tag_search')) {
                $keyword = $request->tag_search;
                $query->where(function($q) use ($keyword) {
                    $q->where('users.role', 'ILIKE', "%{$keyword}%")
                    ->orWhere('users.position', 'ILIKE', "%{$keyword}%")
                    ->orWhere('users.name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('users.email', 'ILIKE', "%{$keyword}%"); 
                });
            }

            return response()->json($query->paginate(20), 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $user = User::find($id);

            if (!$user) return response()->json(['error' => 'Data tidak ditemukan.'], 404);

            $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
                'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048' 
            ]);

            $data = $request->only([
                'name', 'email', 'position', 'company_id', 'role', 
                'phone', 'address', 'hourly_rate', 
                'facebook', 'twitter', 'instagram', 'linkedin'
            ]);

            // Logika Cover: Jika pilih preset
            if ($request->filled('cover_url')) {
                $data['cover_url'] = $request->cover_url;
                $data['cover_image'] = null; // Hapus file lama agar preset aktif
            }

            // Logika Cover: Jika upload file baru
            if ($request->hasFile('cover_image')) {
                $data['cover_image'] = $request->file('cover_image')->store('covers', 'public_uploads');
                $data['cover_url'] = null; // Hapus preset agar file aktif
            }

            if ($request->hasFile('avatar')) {
                $data['avatar_url'] = $request->file('avatar')->store('avatars', 'public_uploads');
            }

            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
            }

            // Karena sudah daftar di $fillable, kita bisa pakai Eloquent dengan aman
            $user->update($data);

            // Update Bank...
            if ($request->bank_name && $request->account_number) {
                DB::table('finance_banks')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'bank_name' => $request->bank_name,
                        'account_name' => $request->account_name ?: $user->name,
                        'account_number' => $request->account_number,
                        'updated_at' => now()
                    ]
                );
            }

            DB::commit();
            return response()->json(['message' => 'Data personel berhasil diperbarui!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $user = User::findOrFail($id);
            $userName = $user->name;

            // Hapus rekening bank terkait terlebih dahulu
            DB::table('finance_banks')->where('user_id', $id)->delete();
            $user->delete();

            AuditLog::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'action'      => 'DELETE_USER',
                'description' => "Menghapus personel: {$userName}",
                'ip_address'  => $request->ip()
            ]);

            DB::commit();
            return response()->json(['message' => 'User berhasil dihapus!'], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Tidak dapat menghapus personel karena masih memiliki data transaksi/kasbon terkait.'], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }
}
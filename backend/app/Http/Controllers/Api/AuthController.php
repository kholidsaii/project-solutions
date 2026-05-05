<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        
        // --- PERBAIKAN DI SINI ---
        AuditLog::create([
            'user_id'     => $user->id, // Gunakan ID (Integer)
            'user_name'   => $user->name, // Masukkan Nama ke kolom user_name
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
        // 1. Validasi semua data yang masuk
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required',
            'company_id' => 'nullable|exists:companies,id',
            'position' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'hourly_rate' => 'nullable|numeric',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048'
        ]);

        // 2. Logika untuk memproses file gambar Avatar
        $avatarPath = null; // Default kosong jika tidak ada upload
        if ($request->hasFile('avatar')) {
            // Simpan file ke storage 'public_uploads' di dalam folder 'avatars'
            $avatarPath = $request->file('avatar')->store('avatars', 'public_uploads');
        }

        // 3. Simpan data user ke database
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
            'company_id' => $request->company_id, 
            'position' => $request->position,      
            'phone' => $request->phone,            
            'hourly_rate' => $request->hourly_rate,
            'avatar_url' => $avatarPath // Sekarang variabel ini sudah terdefinisi!
        ]);

        // 4. Catat ke Audit Log agar aktivitas terlacak
        \App\Models\AuditLog::create([
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'action'      => 'REGISTER',
            'description' => "Mendaftarkan user baru: " . $user->name,
            'ip_address'  => $request->ip()
        ]);

        // 5. Kirim respon sukses ke Vue
        return response()->json([
            'message' => 'User berhasil didaftarkan!',
            'user' => $user
        ], 201);
    }   
    
    
    // Ambil semua daftar user untuk halaman Teamwork (DENGAN FILTER & PAGINATION)
    public function index(Request $request) 
    {
        try {
            // Mulai query dasar
            // PERBAIKAN: Tambahkan 'phone' dan 'hourly_rate' di dalam fungsi select() di bawah ini:
            $query = User::select('id', 'name', 'email', 'role', 'company_id', 'position', 'phone', 'hourly_rate','avatar_url', 'created_at')
                         ->orderBy('created_at', 'desc');

            // --- LOGIKA FILTERING (GLOBAL KEYWORD MATCHING) ---
            // Jika Vue mengirimkan parameter '?tag_search=...'
            if ($request->filled('tag_search')) {
                $keyword = $request->tag_search;
                
                $query->where(function($q) use ($keyword) {
                    // Gunakan ILIKE (khusus PostgreSQL) agar pencarian mengabaikan huruf besar/kecil
                    $q->where('role', 'ILIKE', "%{$keyword}%")
                      ->orWhere('position', 'ILIKE', "%{$keyword}%")
                      ->orWhere('name', 'ILIKE', "%{$keyword}%");
                });
            }

            // --- PAGINATION ---
            // Ganti ->get() menjadi ->paginate(12) agar data diload per 12 baris.
            $users = $query->paginate(6);

            return response()->json($users, 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data user: ' . $e->getMessage()], 500);
        }
    }

    // Update Data Member / Personnel
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['error' => 'Data personel tidak ditemukan.'], 404);
            }

            $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'role'  => 'nullable|string',
                'phone' => 'nullable|string|max:20',
                'hourly_rate' => 'nullable|numeric',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048' // Validasi gambar
            ]);

            // Siapkan data yang akan diupdate
            $dataToUpdate = [
                'name'       => $request->name,
                'email'      => $request->email,
                'position'   => $request->position,
                'company_id' => $request->company_id ?: null,
                'role'       => $request->role ?: $user->role,
                'phone'      => $request->phone,
                'hourly_rate'=> $request->hourly_rate
            ];

            // Jika ada upload avatar baru, simpan dan masukkan ke array update
            if ($request->hasFile('avatar')) {
                $dataToUpdate['avatar_url'] = $request->file('avatar')->store('avatars', 'public_uploads');
            }

            $user->update($dataToUpdate);

            // ... sisa kode audit log biarkan seperti aslinya ...

            return response()->json(['message' => 'Data personel berhasil diperbarui!'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memperbarui data: ' . $e->getMessage()], 500);
        }
    }

    // 3. Hapus Member Team
    public function destroy(Request $request, $id)
    {
        try {
            // Cari user (Akan otomatis lari ke catch ModelNotFoundException jika ID tidak ada)
            $user = User::findOrFail($id);
            
            // Simpan nama sebelum dihapus untuk log
            $userName = $user->name;

            // Eksekusi penghapusan
            $user->delete();

            // CATAT LOG PENGHAPUSAN (Letakkan setelah delete berhasil agar log valid)
            AuditLog::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'action'      => 'DELETE_USER',
                'description' => "Menghapus user member: {$userName}",
                'ip_address'  => $request->ip()
            ]);

            return response()->json(['message' => 'User berhasil dihapus!'], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            // Tangkap error jika user masih terikat dengan tabel lain (seperti kasbon/team_finances)
            return response()->json([
                'error' => 'Tidak dapat menghapus personel karena masih memiliki data transaksi/kasbon terkait.'
            ], 500);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Tangkap error jika ID user sudah tidak ada di database
            return response()->json(['error' => 'Data personel tidak ditemukan.'], 404);
            
        } catch (\Exception $e) {
            // Tangkap error umum lainnya
            return response()->json(['error' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }
    }
    // ... sisanya pastikan user_id dan user_name diisi di create log ...
}
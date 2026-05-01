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
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required',
            'company_id' => 'nullable|exists:companies,id', // Tambahkan ini
            'position' => 'nullable|string'                // Tambahkan ini
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
            'company_id' => $request->company_id, // Simpan ke DB
            'position' => $request->position      // Simpan ke DB
        ]);

        // ... sisa kode log ...
    }
    
    // 1. Ambil semua daftar user untuk halaman Teamwork
    public function index()
    {
        try {
            // TAMBAHKAN company_id dan position di sini Lid!
            $users = User::select('id', 'name', 'email', 'role', 'company_id', 'position', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->get();
            return response()->json($users, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data user'], 500);
        }
    }

    // 4. Update Data Member / Personnel (Sudah digabung dengan Role)
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            // 1. Tambahkan 'role' ke dalam validasi
            $request->validate([
                'name'  => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'role'  => 'nullable|string'
            ]);

            // 2. Simpan perubahan ke database, termasuk role
            $user->update([
                'name'       => $request->name,
                'email'      => $request->email,
                'position'   => $request->position,
                'company_id' => $request->company_id ?: null,
                'role'       => $request->role ?: $user->role, // Update role jika dikirim
            ]);

            // CATAT LOG PERUBAHAN
            \App\Models\AuditLog::create([
                'user_id'     => Auth::id(),
                'user_name'   => Auth::user()->name,
                'action'      => 'UPDATE_USER',
                'description' => "Memperbarui data & role personel: {$user->name}",
                'ip_address'  => $request->ip()
            ]);

            return response()->json(['message' => 'Data personel berhasil diperbarui!'], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
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
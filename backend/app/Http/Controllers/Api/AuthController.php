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

    // 2. Update Role User
    public function updateRole(Request $request, $id)
    {
        $request->validate(['role' => 'required|in:super_admin,admin,member']);
        
        $user = User::findOrFail($id);
        $oldRole = $user->role;
        $user->update(['role' => $request->role]);

        // CATAT LOG PERUBAHAN ROLE
        AuditLog::create([
            'user_id'     => Auth::id(),
            'user_name'   => Auth::user()->name,
            'action'      => 'UPDATE_ROLE',
            'description' => "Mengubah role user {$user->name} dari {$oldRole} ke {$request->role}",
            'ip_address'  => $request->ip()
        ]);

        return response()->json(['message' => 'Role berhasil diperbarui!']);
    }

    // 3. Hapus Member Team
    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Simpan nama sebelum dihapus untuk log
        $userName = $user->name;

        // CATAT LOG PENGHAPUSAN
        AuditLog::create([
            'user_id'     => Auth::id(),
            'user_name'   => Auth::user()->name,
            'action'      => 'DELETE_USER',
            'description' => "Menghapus user member: {$userName}",
            'ip_address'  => $request->ip()
        ]);

        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus!']);
    }
    // ... sisanya pastikan user_id dan user_name diisi di create log ...
}
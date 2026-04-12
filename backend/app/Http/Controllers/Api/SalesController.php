<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    // Ambil semua data leads
    public function index()
    {
        try {
            $leads = DB::table('sales_leads')
                        ->orderBy('created_at', 'desc')
                        ->get();
            
            return response()->json($leads);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil leads'], 500);
        }
    }

    // Simpan leads baru
    public function store(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
        ]);

        DB::table('sales_leads')->insert([
            'client_name' => $request->client_name,
            'company_name' => $request->company_name,
            'status' => 'PROSPECT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Leads berhasil ditambahkan!'], 201);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    // 1. List Project dengan Join Leads
    public function index()
    {
        try {
            $projects = DB::table('projects')
                ->leftJoin('sales_leads', 'projects.lead_id', '=', 'sales_leads.id')
                ->select('projects.*', 'sales_leads.company_name as lead_company')
                ->orderBy('projects.created_at', 'desc')
                ->get();

            return response()->json($projects, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 2. Simpan Project Baru (Input Lengkap)
    public function store(Request $request)
    {
        $request->validate([
            'project_title' => 'required',
            'client_name' => 'required',
            'contract_value' => 'required|numeric',
            'deadline' => 'required|date',
            'category_id' => 'required|exists:work_categories,id'
        ]);

        $id = DB::table('projects')->insertGetId([
            'category_id' => $request->category_id,
            'project_title' => $request->project_title,
            'client_name' => $request->client_name,
            'contract_value' => $request->contract_value,
            'deadline' => $request->deadline,
            'description' => $request->description,
            'progress_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Project berhasil dibuat!', 'id' => $id]);
    }

    // 3. Get Project Detail per Kategori
    public function getByWorkCategory($id)
    {
        // Mengambil project beserta tasks-nya (nesting)
        $projects = DB::table('projects')
            ->where('category_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($projects as $project) {
            $project->tasks = DB::table('project_tasks')
                ->where('project_id', $project->id)
                ->get();
        }

        return response()->json($projects);
    }

    // 4. Task Management
    public function storeTask(Request $request)
    {
        $request->validate(['project_id' => 'required', 'task_name' => 'required']);
        
        $id = DB::table('project_tasks')->insertGetId([
            'project_id' => $request->project_id,
            'task_name'  => $request->task_name,
            'created_at' => now()
        ]);
        return response()->json(['message' => 'Task added', 'id' => $id]);
    }

    public function toggleTask($id)
    {
        $task = DB::table('project_tasks')->where('id', $id)->first();
        DB::table('project_tasks')->where('id', $id)->update([
            'is_completed' => !$task->is_completed,
            'updated_at' => now()
        ]);
        return response()->json(['message' => 'Toggled']);
    }

    // 5. Statistik untuk Analysis.vue
    public function getStats()
    {
        $summary = [
            'total'    => DB::table('projects')->count(),
            'finish'   => DB::table('projects')->where('progress_percent', 100)->count(),
            'progress' => DB::table('projects')->whereBetween('progress_percent', [1, 99])->count(),
            'planing'  => DB::table('projects')->where('progress_percent', 0)->count(),
            'pending'  => DB::table('projects')->where('priority', 1)->count(), // Contoh High Priority sebagai pending
        ];

        $monthlyData = DB::table('projects')
            ->select(DB::raw("EXTRACT(MONTH FROM created_at) as month"), DB::raw("COUNT(*) as total"))
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $monthlyStats = array_fill(0, 12, 0);
        foreach ($monthlyData as $data) {
            $monthlyStats[(int)$data->month - 1] = (int)$data->total;
        }

        return response()->json([
            'summary'    => $summary,
            'monthly'    => $monthlyStats,
            'categories' => [10, 20, 30], // Dummy atau hitung berdasar kategori
        ]);
    }
    public function getCategories() {
    try {
        $categories = DB::table('work_categories')->get();
        return response()->json($categories, 200);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}
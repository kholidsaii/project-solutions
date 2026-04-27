<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class ProjectController extends Controller
{
    private function getTableName($type)
    {
        return match($type) {
            'categories' => 'work_categories',
            'status'     => 'work_statuses',
            'priority'   => 'work_priorities',
            'package'    => 'work_packages',
            'projects'   => 'projects', // Tambahkan ini agar bisa akses tabel project langsung
            default      => null
        };
    }
   public function index()
    {
        try {
            $projects = DB::table('projects')
                ->leftJoin('work_categories', 'projects.category_id', '=', 'work_categories.id')
                ->select(
                    'projects.*', 
                    'work_categories.name as category_name',
                    'work_categories.image_path as category_logo', // AMBIL LOGO DI SINI
                    DB::raw("(finish_date - start_date) as total_day_diff") 
                )
                ->orderBy('projects.created_at', 'desc')
                ->get();

            $formatted = $projects->map(function($p) {
                return [
                    'id' => $p->id,
                    'project_title' => $p->project_title,
                    'client_name' => $p->client_name ?? '-',
                    'start_date' => $p->start_date ? Carbon::parse($p->start_date)->format('d F Y') : '-',
                    'finish_date' => $p->finish_date ? Carbon::parse($p->finish_date)->format('d F Y') : '-',
                    'total_day' => ($p->total_day_diff ?? 0) . ' Day',
                    'category' => $p->category_name,
                    'logo' => $p->category_logo ? 'uploads/' . $p->category_logo : null,// Logo sekarang terisi path (misal: categories/abc.jpg)
                    'status' => $p->status,
                    'priority' => $p->priority,
                    'package' => $p->package ?? '-',
                    'progress' => $p->progress_percent,
                    'color' => $p->status == 'Finish' ? 'bg-emerald-500' : 'bg-blue-600'
                ];
            });

            return response()->json($formatted, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_title' => 'required|string',
            'client_name' => 'required|string',
            'contract_value' => 'required|numeric',
            'deadline' => 'required|date',
            'category_id' => 'required|exists:work_categories,id'
        ]);

        try {
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
            return response()->json(['message' => 'Project berhasil dibuat!', 'id' => $id], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menyimpan project'], 500);
        }
    }

    public function show($id)
    {
        $project = DB::table('projects')
            ->leftJoin('work_categories', 'projects.category_id', '=', 'work_categories.id')
            ->select('projects.*', 'work_categories.name as category_name')
            ->where('projects.id', $id)
            ->first();

        if (!$project) return response()->json(['message' => 'Project tidak ditemukan'], 404);

        $project->tasks = DB::table('project_tasks')->where('project_id', $id)->get();
        return response()->json($project);
    }

    public function getByWorkCategory($id)
    {
        $projects = DB::table('projects')->where('category_id', $id)->orderBy('created_at', 'desc')->get();
        foreach ($projects as $project) {
            $project->tasks = DB::table('project_tasks')->where('project_id', $project->id)->get();
        }
        return response()->json($projects);
    }

    public function storeTask(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id', 'task_name' => 'required']);
        $id = DB::table('project_tasks')->insertGetId([
            'project_id' => $request->project_id,
            'task_name'  => $request->task_name,
            'is_completed' => false,
            'created_at' => now()
        ]);
        
        // Auto-update progress project sederhana (opsional)
        $this->updateProjectProgress($request->project_id);

        return response()->json(['message' => 'Task added', 'id' => $id]);
    }

    public function toggleTask($id)
    {
        $task = DB::table('project_tasks')->where('id', $id)->first();
        if(!$task) return response()->json(['message' => 'Task not found'], 404);

        DB::table('project_tasks')->where('id', $id)->update([
            'is_completed' => !$task->is_completed,
            'updated_at' => now()
        ]);

        $this->updateProjectProgress($task->project_id);
        return response()->json(['message' => 'Task Toggled']);
    }

    private function updateProjectProgress($projectId)
    {
        $total = DB::table('project_tasks')->where('project_id', $projectId)->count();
        if ($total > 0) {
            $completed = DB::table('project_tasks')->where('project_id', $projectId)->where('is_completed', true)->count();
            $percent = round(($completed / $total) * 100);
            DB::table('projects')->where('id', $projectId)->update(['progress_percent' => $percent]);
        }
    }

    public function getStats()
    {
        $summary = [
            'total'    => DB::table('projects')->count(),
            'finish'   => DB::table('projects')->where('progress_percent', 100)->count(),
            'progress' => DB::table('projects')->whereBetween('progress_percent', [1, 99])->count(),
            'planing'  => DB::table('projects')->where('progress_percent', 0)->count(),
            'pending'  => DB::table('projects')->where('contract_value', '>', 100000000)->count(), // Contoh kriteria pending/high value
        ];

        // Data Bulanan
        $monthlyData = DB::table('projects')
            ->select(DB::raw("EXTRACT(MONTH FROM created_at) as month"), DB::raw("COUNT(*) as total"))
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')->get();

        $monthlyStats = array_fill(0, 12, 0);
        foreach ($monthlyData as $data) {
            $monthlyStats[(int)$data->month - 1] = (int)$data->total;
        }

        // Data Pie Chart berdasarkan Kategori
        $categoryDistribution = DB::table('projects')
            ->join('work_categories', 'projects.category_id', '=', 'work_categories.id')
            ->select('work_categories.name', DB::raw('count(*) as total'))
            ->groupBy('work_categories.name')
            ->pluck('total')->toArray();

        return response()->json([
            'summary' => $summary,
            'monthly' => $monthlyStats,
            'categories' => $categoryDistribution ?: [25, 25, 25, 25],
        ]);
    }

    public function getCategories()
    {
        return response()->json(DB::table('work_categories')->get(), 200);
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required']);
        DB::table('work_categories')->insert([
            'name' => $request->name,
            'icon' => $request->icon ?? 'fas fa-project-diagram',
            'created_at' => now()
        ]);
        return response()->json(['message' => 'Category created']);
    }

   public function getAllReports()
    {
        try {
            $reports = DB::table('projects')
                ->leftJoin('work_categories', 'projects.category_id', '=', 'work_categories.id')
                ->select(
                    'projects.id',
                    // Ambil project_title dan kirim sebagai client_name ke Vue
                    'projects.project_title as client_name', 
                    'work_categories.name as category',
                    'projects.progress_percent as score',
                    DB::raw("CASE 
                        WHEN projects.progress_percent = 100 THEN 'COMPLETED' 
                        WHEN projects.progress_percent > 0 THEN 'ON TRACK' 
                        ELSE 'PLANNING' 
                    END as status"),
                    DB::raw("TO_CHAR(projects.created_at, 'DD/MM/YYYY') as date")
                )
                ->orderBy('projects.created_at', 'desc')
                ->get();

            return response()->json($reports, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMasterData()
    {
        // Pastikan urutan dan nama key sesuai dengan yang dipanggil allMasterData di Vue
        return response()->json([
            'categories' => DB::table('work_categories')->orderBy('id', 'asc')->get(),
            'status'     => DB::table('work_statuses')->orderBy('id', 'asc')->get(),
            'priority'   => DB::table('work_priorities')->orderBy('id', 'asc')->get(),
            'package'    => DB::table('work_packages')->orderBy('id', 'asc')->get(),
        ]);
    }

    public function storeMaster(Request $request, $type)
    {
        $table = $this->getTableName($type);
        if (!$table) return response()->json(['message' => 'Invalid type'], 400);

        try {
            $data = ['name' => $request->name, 'created_at' => now()];

            // Jika menambah kategori baru, sekalian siapkan field projectnya
            if ($type === 'categories') {
                $data['icon'] = $request->icon ?? 'fas fa-folder';
                $data['client_name'] = $request->client_name ?? '-';
                
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $path = $file->store('categories', 'public_uploads');
                    $data['image_path'] = $path; 
                }
            }

            DB::table($table)->insert($data);
            return response()->json(['message' => 'Berhasil disimpan']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateMaster(Request $request, $type, $id)
    {
        $table = $this->getTableName($type);
        if (!$table) return response()->json(['message' => 'Tipe tidak valid'], 400);

        try {
            // JIKA YANG DIEDIT ADALAH KATEGORI (PROJECT TITLE)
            // Kita harus update tabel 'projects' agar field Client, Date, dll masuk.
            if ($type === 'categories') {
                $data = [
                    'project_title' => $request->name,
                    'client_name'   => $request->client_name,
                    'start_date'    => $request->start_date,
                    'finish_date'   => $request->finish_date,
                    'package'       => $request->package,
                    'updated_at'    => now()
                ];

                // Hilangkan nilai null agar tidak menimpa data lama
                $data = array_filter($data, fn($value) => !is_null($value));

                // Cek jika ada upload logo baru di tabel project
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $path = $file->store('categories', 'public_uploads');
                    $data['logo'] = $path; // Di tabel projects kolomnya 'logo'
                }

                // Update ke tabel projects (bukan work_categories) 
                // karena kita menganggap kategori di UI adalah representasi Project
                DB::table('projects')->where('id', $id)->update($data);

                return response()->json(['message' => 'Detail Project berhasil diupdate!']);
            }

            // JIKA YANG DIEDIT ADALAH MASTER DATA (Status/Priority/Package)
            // Cukup update kolom 'name' saja di tabel masternya
            DB::table($table)->where('id', $id)->update([
                'name'       => $request->name,
                'updated_at' => now()
            ]);

            return response()->json(['message' => 'Master ' . $type . ' berhasil diupdate!']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Perbaikan fungsi delete: harus dinamis sesuai tab yang dipilih
    public function deleteMaster($type, $id)
    {
        $table = $this->getTableName($type);
        if (!$table) return response()->json(['message' => 'Invalid type'], 400);

        try {
            DB::table($table)->where('id', $id)->delete();
            return response()->json(['message' => 'Data berhasil dihapus!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus data'], 500);
        }
    }
    public function getMasterDataByType($type) {
        $table = $this->getTableName($type);
        if (!$table) return response()->json([], 400);
        
        return response()->json(DB::table($table)->orderBy('id', 'asc')->get());
    }
    // 1. Fungsi Update Khusus Detail Project (Dipanggil dari Dashboard Overview)
public function updateProjectDetail(Request $request, $id)
{
    try {
        $data = [
            'project_title'   => $request->project_title,
            'client_name'     => $request->client_name,
            'contract_value'  => $request->contract_value,
            'category_id'     => $request->category_id,
            'start_date'      => $request->start_date,
            'finish_date'     => $request->finish_date,
            'description'     => $request->description,
            'status'          => $request->status,
            'priority'        => $request->priority,
            'package'         => $request->package,
            'updated_at'      => now()
        ];

        // Pembersihan data null
        $data = array_filter($data, fn($value) => !is_null($value));

        DB::table('projects')->where('id', $id)->update($data);

        return response()->json(['message' => 'Laporan Project Diperbarui'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}
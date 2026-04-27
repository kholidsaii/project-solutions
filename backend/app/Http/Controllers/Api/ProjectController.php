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
        return response()->json([
            'categories' => DB::table('work_categories')->get(),
            'statuses'   => DB::table('work_statuses')->get(),
            'priorities' => DB::table('work_priorities')->get(),
            'packages'   => DB::table('work_packages')->get(),
        ]);
    }

    public function storeMaster(Request $request, $type)
    {
        $table = $this->getTableName($type);
        
        $data = [
            'name' => $request->name,
            'created_at' => now()
        ];

        if ($type === 'categories' && $request->hasFile('image')) {
            $file = $request->file('image');
            
            // Simpan di folder: public/uploads/categories
            // Laravel otomatis kasih nama unik
            $path = $file->store('categories', 'public_uploads'); 
            
            $data['image_path'] = $path; // Simpan path-nya saja: "categories/nama_file.jpg"
        }

        DB::table($table)->insert($data);
        return response()->json(['message' => 'Saved!']);
    }
    public function updateMaster(Request $request, $type, $id)
    {
        // Sekarang $this->getTableName sudah ada, jadi tidak akan undefined lagi
        $table = $this->getTableName($type);
        
        if (!$table) {
            return response()->json(['message' => 'Tipe master data tidak valid'], 400);
        }

        try {
            $data = [
                'name'       => $request->name,
                'updated_at' => now()
            ];

            // Khusus kategori kalau ada icon atau image
            if ($type === 'categories') {
                if ($request->has('icon')) {
                    $data['icon'] = $request->icon;
                }
                
               if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $path = $file->store('categories', 'public_uploads'); 
                    
                    // CUKUP SIMPAN $path SAJA! 
                    // Hasilnya nanti cuma: "categories/nama_file.jpg"
                    $data['image_path'] = $path; 
                }
            }

            DB::table($table)->where('id', $id)->update($data);

            return response()->json(['message' => 'Data berhasil diupdate!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {
        try {
            DB::table('projects')->where('id', $id)->delete();
            return response()->json(['message' => 'Project berhasil dihapus!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus project'], 500);
        }
    }
}
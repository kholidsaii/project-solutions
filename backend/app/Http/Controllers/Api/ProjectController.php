<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Company;

class ProjectController extends Controller
{
    private function createLog($action, $description, $request)
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'user_name'   => Auth::user()->name,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $request->ip()
        ]);
    }
    
    private function getTableName($type)
    {
        return match($type) {
            'categories' => 'work_categories',
            'status'     => 'work_statuses',
            'priority'   => 'work_priorities',
            'package'    => 'work_packages',
            'color'      => 'work_colors',
            'projects'   => 'projects',
            'banks'      => 'finance_banks',
            'labels'     => 'finance_labels',
            'tags_works' => 'master_tags_works',
            'tags_team'  => 'master_tags_team',
            'tags_docs'  => 'master_tags_docs',
            default      => null
        };
    }   

    public function index(Request $request)
    {
        try {
            $query = DB::table('projects')
                ->leftJoin('work_categories', 'projects.category_id', '=', 'work_categories.id')
                ->select(
                    'projects.*', 
                    'work_categories.name as category_name',
                    'work_categories.image_path as category_logo',
                    DB::raw("(projects.finish_date - projects.start_date) as total_day_diff"), 
                    DB::raw("(
                        SELECT COUNT(DISTINCT users.id) 
                        FROM users 
                        JOIN project_companies ON users.company_id = project_companies.company_id 
                        WHERE project_companies.project_id = projects.id
                    ) as team_count")
                );

            if ($request->has('company_id')) {
                $query->whereExists(function ($q) use ($request) {
                    $q->select(DB::raw(1))
                      ->from('project_companies')
                      ->whereColumn('project_companies.project_id', 'projects.id')
                      ->where('project_companies.company_id', $request->company_id);
                });
            }

            $projects = $query->orderBy('projects.created_at', 'desc')->get();

            // Ambil mapping Many-to-Many Companies untuk semua projects sekaligus
            $projectCompanies = DB::table('project_companies')
                ->join('companies', 'project_companies.company_id', '=', 'companies.id')
                ->select('project_companies.project_id', 'companies.id', 'companies.name')
                ->get()
                ->groupBy('project_id');

            $formatted = $projects->map(function($p) use ($projectCompanies) {
                return [
                    'id' => $p->id,
                    'project_title' => $p->project_title,
                    'client_name' => $p->client_name ?? '-',
                    'start_date' => $p->start_date ? Carbon::parse($p->start_date)->format('d F Y') : '-',
                    'finish_date' => $p->finish_date ? Carbon::parse($p->finish_date)->format('d F Y') : '-',
                    'total_day_diff' => $p->total_day_diff ?? 0,
                    'category' => $p->category_name,
                    'logo' => $p->logo ? $p->logo : $p->category_logo,
                    'status' => $p->status,
                    'priority' => $p->priority,
                    'package' => $p->package ?? '-',
                    'progress' => $p->progress_percent,
                    'contract_value' => (float)$p->contract_value,
                    'team_count' => (int) $p->team_count, 
                    'color' => $p->status == 'Finish' ? 'bg-emerald-500' : 'bg-blue-600',
                    
                    // TAMBAHAN: Masukkan array perusahaan yang terikat dengan project ini
                    'companies' => isset($projectCompanies[$p->id]) ? $projectCompanies[$p->id] : [],
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
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('projects', 'public_uploads');
            }
            $id = DB::table('projects')->insertGetId([
                'category_id' => $request->category_id,
                'company_id' => $request->company_id, // Fallback
                'project_title' => $request->project_title,
                'client_name' => $request->client_name,
                'contract_value' => $request->contract_value,
                'deadline' => $request->deadline,
                'description' => $request->description,
                'logo' => $logoPath,
                'progress_percent' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->createLog('CREATE_PROJECT', "Membuat project baru: {$request->project_title}", $request);
            return response()->json(['message' => 'Project berhasil dibuat!', 'id' => $id], 201);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menyimpan project'], 500);
        }
    }

    public function getNotifications()
    {
        try {
            $logs = DB::table('audit_logs')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($log) {
                    return [
                        'id'      => $log->id,
                        'title'   => $log->action, 
                        'message' => $log->description,
                        'time'    => Carbon::parse($log->created_at)->diffForHumans()
                    ];
                });

            return response()->json($logs, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $project = DB::table('projects')
            ->leftJoin('work_categories', 'projects.category_id', '=', 'work_categories.id')
            // Join pertama ke project_companies untuk mendapatkan PT utama
            ->leftJoin('project_companies', function($join) {
                $join->on('projects.id', '=', 'project_companies.project_id');
            })
            ->leftJoin('companies', 'project_companies.company_id', '=', 'companies.id')
            ->select(
                'projects.*', 
                'work_categories.name as category_name', 
                'work_categories.image_path as category_logo',
                'companies.name as affiliated_pt_name'
            )
            ->where('projects.id', $id)
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        $totalPurchasing = DB::table('project_purchasings')->where('project_id', $id)->sum('total_price');
        $totalWorkOrder = DB::table('work_orders')->where('project_id', $id)->sum('budget');
        
        $project->financials = [
            'total_expense' => (float)($totalPurchasing + $totalWorkOrder),
            'total_purchasing' => (float)$totalPurchasing,
            'total_work_order' => (float)$totalWorkOrder,
            'remaining_budget' => (float)($project->contract_value - ($totalPurchasing + $totalWorkOrder))
        ];

        // Many to Many relations untuk Project ini
        $project->companies = DB::table('project_companies')
            ->join('companies', 'project_companies.company_id', '=', 'companies.id')
            ->where('project_companies.project_id', $id)
            ->select('companies.id', 'companies.name')
            ->get();

        $project->team = DB::table('project_teams')
            ->join('users', 'project_teams.user_id', '=', 'users.id')
            ->where('project_teams.project_id', $id)
            ->select('users.id', 'users.name', 'users.position', 'project_teams.role')
            ->get();

        foreach ($project->team as $member) {
            $member->tasks_count = DB::table('project_tasks')
                ->where('project_id', $id)
                ->where('assigned_to', $member->id)
                ->count();
        }

        $project->tasks = DB::table('project_tasks')->where('project_id', $id)->orderBy('id', 'asc')->get();
        $project->days_left = $project->deadline ? Carbon::parse($project->deadline)->diffInDays(now(), false) : null;
        $project->work_orders = DB::table('work_orders')->where('project_id', $id)->orderBy('id', 'desc')->get();
        $project->purchasings = DB::table('project_purchasings')
            ->leftJoin('users', 'project_purchasings.user_id', '=', 'users.id')
            ->where('project_purchasings.project_id', $id)
            ->select('project_purchasings.*', 'users.name as buyer_name')
            ->orderBy('purchase_date', 'desc')
            ->get();

        $project->productions = DB::table('project_productions')
            ->leftJoin('users', 'project_productions.user_id', '=', 'users.id')
            ->where('project_productions.project_id', $id)
            ->select('project_productions.*', 'users.name as creator_name')
            ->get();

        $project->documents = DB::table('project_documents')
            ->leftJoin('users', 'project_documents.user_id', '=', 'users.id')
            ->where('project_documents.project_id', $id)
            ->select('project_documents.*', 'users.name as uploader_name')
            ->get();

        $project->marketings = DB::table('project_marketings')->where('project_id', $id)->get();
        $project->supports = DB::table('project_supports')
            ->leftJoin('users as reporters', 'project_supports.user_id', '=', 'reporters.id')
            ->leftJoin('users as assigned', 'project_supports.assigned_to', '=', 'assigned.id')
            ->where('project_supports.project_id', $id)
            ->select('project_supports.*', 'reporters.name as reporter_name', 'assigned.name as assigned_name')
            ->get();

        $project->invoices = DB::table('project_invoices')->where('project_id', $id)->orderBy('created_at', 'desc')->get();
            
        $project->payment_summary = [
            'total_paid' => (float)DB::table('project_invoices')->where('project_id', $id)->where('status', 'Paid')->sum('amount'),
            'total_unpaid' => (float)DB::table('project_invoices')->where('project_id', $id)->where('status', 'Unpaid')->sum('amount'),
        ];

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
        $request->validate(['project_id' => 'required', 'task_name' => 'required']);
        DB::table('project_tasks')->insert([
            'project_id' => $request->project_id,
            'task_name' => $request->task_name,
            'task_category' => $request->task_category ?? 'GENERAL',
            'priority' => $request->priority ?? 'Medium',
            'is_completed' => false,
            'created_at' => now()
        ]);
        $this->updateProjectProgress($request->project_id);
        $this->createLog('ADD_TASK', "Menambah task '{$request->task_name}' ke project ID: {$request->project_id}", $request);
        return response()->json(['message' => 'Task Created']);
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
        try {
            $summary = [
                'total'    => (int) DB::table('projects')->count(),
                'finish'   => (int) DB::table('projects')->where('progress_percent', 100)->count(),
                'progress' => (int) DB::table('projects')->whereBetween('progress_percent', [1, 99])->count(),
                'planing'  => (int) DB::table('projects')->where('progress_percent', 0)->count(),
                'pending'  => (int) DB::table('projects')->where('contract_value', '>', 100000000)->count(),
            ];

            $monthlyData = DB::table('projects')
                ->select(DB::raw("CAST(EXTRACT(MONTH FROM created_at) AS INTEGER) as month"), DB::raw("COUNT(*) as total"))
                ->whereYear('created_at', date('Y'))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $monthlyStats = array_fill(0, 12, 0);
            foreach ($monthlyData as $data) {
                if ($data->month >= 1 && $data->month <= 12) {
                    $monthlyStats[$data->month - 1] = (int) $data->total;
                }
            }

            $categoryDistribution = DB::table('projects')
                ->join('work_categories', 'projects.category_id', '=', 'work_categories.id')
                ->select('work_categories.name', DB::raw('COUNT(*) as total'))
                ->groupBy('work_categories.name')
                ->pluck('total')
                ->map(fn($val) => (int) $val) 
                ->toArray();

            return response()->json([
                'summary'    => $summary,
                'monthly'    => $monthlyStats,
                'categories' => !empty($categoryDistribution) ? $categoryDistribution : [0, 0, 0, 0],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'summary' => ['total' => 0, 'finish' => 0, 'progress' => 0, 'planing' => 0, 'pending' => 0],
                'monthly' => array_fill(0, 12, 0),
                'categories' => [0, 0, 0, 0],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCategories() { return response()->json(DB::table('work_categories')->get(), 200); }

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
                    'projects.project_title as client_name', 
                    'work_categories.name as category',
                    'projects.progress_percent as score',
                    DB::raw("CASE WHEN projects.progress_percent = 100 THEN 'COMPLETED' WHEN projects.progress_percent > 0 THEN 'ON TRACK' ELSE 'PLANNING' END as status"),
                    DB::raw("TO_CHAR(projects.created_at, 'DD/MM/YYYY') as date")
                )
                ->orderBy('projects.created_at', 'desc')
                ->get();
            return response()->json($reports, 200);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function getMasterData()
    {
        return response()->json([
            'categories' => DB::table('work_categories')->orderBy('id', 'asc')->get(),
            'status'     => DB::table('work_statuses')->orderBy('id', 'asc')->get(),
            'priority'   => DB::table('work_priorities')->orderBy('id', 'asc')->get(),
            'package'    => DB::table('work_packages')->orderBy('id', 'asc')->get(),
            'color'      => DB::table('work_colors')->orderBy('id', 'asc')->get(), 
        ]);
    }

    public function storeMaster(Request $request, $type)
    {
        $table = $this->getTableName($type);
        if (!$table) return response()->json(['message' => 'Invalid type'], 400);

        try {
            $data = ['name' => $request->name, 'created_at' => now()];

            if ($type === 'categories') {
                $data['icon'] = $request->icon ?? 'fas fa-folder';
                $data['client_name'] = $request->client_name ?? '-';
                if ($request->hasFile('image')) {
                    $data['image_path'] = $request->file('image')->store('categories', 'public_uploads'); 
                }
            }

            DB::table($table)->insert($data);
            return response()->json(['message' => 'Berhasil disimpan']);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function updateMaster(Request $request, $type, $id)
    {
        $table = $this->getTableName($type);
        if (!$table) return response()->json(['message' => 'Tipe tidak valid'], 400);

        try {
            if ($type === 'categories') {
                $data = [
                    'project_title' => $request->name,
                    'client_name'   => $request->client_name,
                    'start_date'    => $request->start_date,
                    'finish_date'   => $request->finish_date,
                    'package'       => $request->package,
                    'updated_at'    => now()
                ];
                $data = array_filter($data, fn($value) => !is_null($value));
                if ($request->hasFile('image')) {
                    $data['logo'] = $request->file('image')->store('categories', 'public_uploads'); 
                }
                DB::table('projects')->where('id', $id)->update($data);
                return response()->json(['message' => 'Detail Project berhasil diupdate!']);
            }

            DB::table($table)->where('id', $id)->update(['name' => $request->name, 'updated_at' => now()]);
            return response()->json(['message' => 'Master ' . $type . ' berhasil diupdate!']);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function deleteMaster($type, $id)
    {
        $table = $this->getTableName($type);
        if (!$table) return response()->json(['message' => 'Invalid type'], 400);

        try {
            DB::table($table)->where('id', $id)->delete();
            return response()->json(['message' => 'Data berhasil dihapus!'], 200);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal menghapus data'], 500); }
    }

    public function getMasterDataByType($type) {
        $table = $this->getTableName($type);
        if (!$table) return response()->json([], 400);
        return response()->json(DB::table($table)->orderBy('id', 'asc')->get());
    }

    public function updateProjectDetail(Request $request, $id)
    {
        try {
            $project = DB::table('projects')->where('id', $id)->first();
            if (!$project) return response()->json(['message' => 'Project tidak ditemukan'], 404);

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
                'company_id'      => $request->company_id, // Fallback
                'updated_at'      => now()
            ];
            if ($request->hasFile('logo')) {
                $data['logo'] = $request->file('logo')->store('projects', 'public_uploads');
            }
            $data = array_filter($data, fn($value) => !is_null($value));

            DB::table('projects')->where('id', $id)->update($data);

            $titleForLog = $request->project_title ?? $project->project_title;
            $this->createLog('UPDATE_PROJECT', "Memperbarui detail project: {$titleForLog}", $request);

            return response()->json(['message' => 'Laporan Project Diperbarui'], 200);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function updateTask(Request $request, $id)
    {
        DB::table('project_tasks')->where('id', $id)->update(['description' => $request->description, 'updated_at' => now()]);
        return response()->json(['message' => 'Documentation Updated']);
    }

    public function deleteTask($id)
    {
        $task = DB::table('project_tasks')->where('id', $id)->first();
        if (!$task) return response()->json(['message' => 'Not found'], 404);
        
        $projectId = $task->project_id;
        DB::table('project_tasks')->where('id', $id)->delete();
        $this->updateProjectProgress($projectId);
        
        return response()->json(['message' => 'Task deleted']);
    }

    public function getWorkOrders($projectId)
    {
        $orders = DB::table('work_orders')->where('project_id', $projectId)->orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    public function storeWorkOrder(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id', 'title' => 'required|string', 'budget' => 'numeric']);
        try {
            $id = DB::table('work_orders')->insertGetId([
                'project_id'  => $request->project_id,
                'title'       => $request->title,
                'description' => $request->description,
                'pic_name'    => $request->pic_name ?? 'SUHERY',
                'budget'      => $request->budget ?? 0,
                'status'      => 'Draft',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            return response()->json(['message' => 'Work Order Dispatched', 'id' => $id], 201);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function updateWorkOrder(Request $request, $id)
    {
        try {
            DB::table('work_orders')->where('id', $id)->update([
                'title'       => $request->title, 'description' => $request->description,
                'pic_name'    => $request->pic_name, 'budget' => $request->budget,
                'status'      => $request->status, 'updated_at' => now(),
            ]);
            return response()->json(['message' => 'Work Order Updated']);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function deleteWorkOrder($id)
    {
        try {
            DB::table('work_orders')->where('id', $id)->delete();
            return response()->json(['message' => 'Work Order Deleted']);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal menghapus Work Order'], 500); }
    }

    public function syncProjectCompanies(Request $request, $projectId)
    {
        $request->validate(['company_ids' => 'required|array']);
        try {
            DB::beginTransaction();
            
            // Cari PT mana saja yang saat ini terikat
            $currentCompanies = DB::table('project_companies')
                ->where('project_id', $projectId)
                ->pluck('company_id')
                ->toArray();

            // 1. PT yang baru dicentang (belum ada di DB) -> INSERT
            $toAdd = array_diff($request->company_ids, $currentCompanies);
            foreach ($toAdd as $id) {
                DB::table('project_companies')->insert([
                    'project_id' => $projectId,
                    'company_id' => $id,
                    'role'       => 'Partner',
                    'created_at' => now()
                ]);
            }

            // 2. PT yang di-uncheck (ada di DB tapi tidak ada di request) -> DELETE
            // HANYA hapus jika tidak ada transaksi terikat (untuk keamanan)
            $toRemove = array_diff($currentCompanies, $request->company_ids);
            foreach ($toRemove as $id) {
                $hasTransactions = DB::table('finance_transactions')
                    ->where('project_id', $projectId)
                    ->where('company_id', $id)
                    ->exists();
                
                if (!$hasTransactions) {
                    DB::table('project_companies')
                        ->where('project_id', $projectId)
                        ->where('company_id', $id)
                        ->delete();
                } else {
                    // Beri peringatan atau lewati penghapusan karena ada data keuangan
                    continue; 
                }
            }

            DB::commit();
            return response()->json(['message' => 'Project Companies Synced Safely!']);
        } catch (\Exception $e) {
            DB::rollBack(); 
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function showProjectCompanies($id)
    {
        $project = Project::with('companies')->find($id);
        if (!$project) return response()->json(['message' => 'Project not found'], 404);
        return response()->json($project);
    }

    public function storeProduction(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id', 'title' => 'required', 'type' => 'required', 'content' => 'required']);
        DB::table('project_productions')->insert([
            'project_id' => $request->project_id, 'user_id' => Auth::id(), 
            'title' => $request->title, 'type' => $request->type, 'content' => $request->content,
            'version' => $request->version ?? '1.0.0', 'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['message' => 'Product Deliverable Saved']);
    }

    public function indexDocuments(Request $request)
    {
        $query = DB::table('activity_documents')
            ->leftJoin('users', 'activity_documents.user_id', '=', 'users.id')
            ->select('activity_documents.*', 'users.name as uploader_name');
        if ($request->has('activity_id')) $query->where('activity_id', $request->activity_id);
        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function storeDocuments(Request $request)
    {
        $request->validate(['activity_id' => 'required|integer', 'title' => 'required|string|max:255', 'document' => 'required|file|max:20480']);
        try {
            $file = $request->file('document');
            $path = $file->store('documents', 'public_uploads');
            DB::table('activity_documents')->insert([
                'activity_id' => $request->activity_id, 'user_id' => Auth::id(), 'title' => $request->title,
                'file_path' => $path, 'file_type' => $file->getClientOriginalExtension(), 'file_size' => $file->getSize(), 'created_at' => now()
            ]);
            return response()->json(['message' => 'Dokumen berhasil diunggah!'], 201);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal mengunggah dokumen: ' . $e->getMessage()], 500); }
    }

    public function destroyDocuments($id)
    {
        try {
            $doc = DB::table('activity_documents')->where('id', $id)->first();
            if (!$doc) return response()->json(['message' => 'Dokumen tidak ditemukan'], 404);
            DB::table('activity_documents')->where('id', $id)->delete();
            return response()->json(['message' => 'Dokumen berhasil dihapus!']);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal menghapus dokumen'], 500); }
    }

    public function storeSupport(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id', 'subject' => 'required|string|max:255', 'priority' => 'required', 'message' => 'required']);
        DB::table('project_supports')->insert([
            'project_id'  => $request->project_id, 'user_id' => Auth::id(), 'assigned_to' => $request->assigned_to, 
            'subject' => $request->subject, 'priority' => $request->priority, 'status' => 'Open',
            'message' => $request->message, 'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['message' => 'Support Ticket Created']);
    }

    public function updateSupportStatus(Request $request, $id)
    {
        DB::table('project_supports')->where('id', $id)->update(['status' => $request->status, 'updated_at' => now()]);
        return response()->json(['message' => 'Ticket Status Updated']);
    }

    public function storeMarketing(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id', 'title' => 'required|string', 'type' => 'required']);
        DB::table('project_marketings')->insert([
            'project_id' => $request->project_id, 'user_id' => Auth::id(), 'title' => $request->title,
            'type' => $request->type, 'budget_estimate' => $request->budget_estimate ?? 0,
            'next_follow_up' => $request->next_follow_up, 'status' => 'Leads', 'notes' => $request->notes,
            'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['message' => 'Marketing Lead Created']);
    }

    public function storePurchasing(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id', 'item_name' => 'required|string', 'amount' => 'required|numeric', 'quantity' => 'required|numeric']);

        try {
            DB::beginTransaction();

            $totalPrice = $request->amount * $request->quantity;
            $project = DB::table('projects')->where('id', $request->project_id)->first();
            
            // Ambil relasi dari project_companies sebagai referensi PT
            $linkedCompany = DB::table('project_companies')->where('project_id', $request->project_id)->first();
            $ptId = $linkedCompany ? $linkedCompany->company_id : $project->company_id;

            DB::table('project_purchasings')->insert([
                'project_id' => $request->project_id, 'user_id' => Auth::id(), 'item_name' => $request->item_name,
                'vendor_name' => $request->vendor_name, 'amount' => $request->amount, 'quantity' => $request->quantity,
                'total_price' => $totalPrice, 'purchase_date' => $request->purchase_date ?? now(),
                'status' => 'Paid', 'created_at' => now(), 'updated_at' => now()
            ]);

            DB::table('accounting_journals')->insert([
                'pt_id' => $ptId,
                'project_id' => $project->id,
                'coa_id' => 4, 
                'transaction_date' => $request->purchase_date ?? now(),
                'description' => "BELANJA: " . $request->item_name,
                'debit' => 0,
                'credit' => (float)$totalPrice,
                'reference_type' => 'Purchasing',
                'created_at' => now()
            ]);

            DB::commit();
            return response()->json(['message' => 'Purchase Record Saved & Journaled']);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deletePurchasing($id)
    {
        try {
            DB::table('project_purchasings')->where('id', $id)->delete();
            return response()->json(['message' => 'Purchase record deleted']);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal menghapus data belanja'], 500); }
    }

    public function storeInvoice(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id', 'title' => 'required', 'amount' => 'required|numeric', 'due_date' => 'required|date']);
        $invNumber = 'INV-' . $request->project_id . '-' . time();
        DB::table('project_invoices')->insert([
            'project_id' => $request->project_id, 'invoice_number' => $invNumber, 'title' => $request->title,
            'amount' => $request->amount, 'due_date' => $request->due_date, 'status' => 'Unpaid',
            'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['message' => 'Invoice Created Successfully']);
    }

    public function getTeamworkSummary()
    {
        try {
            $members = DB::table('users')
                ->leftJoin('companies', 'users.company_id', '=', 'companies.id')
                ->leftJoin('team_finances', function($join) {
                    $join->on('users.id', '=', 'team_finances.user_id')->where('team_finances.status', '=', 'Pending');
                })
                ->select(
                    'users.id', 'users.name', 'users.email', 'users.role', 'users.position', 'users.company_id',
                    'companies.name as pt_owner_name', DB::raw('COALESCE(SUM(team_finances.amount), 0) as outstanding')
                )
                ->groupBy('users.id', 'users.name', 'users.email', 'users.role', 'users.position', 'users.company_id', 'companies.name')
                ->get();

            $organizations = DB::table('companies')->select('id', 'name', 'legal_name')->get();
            $totalProjects = DB::table('projects')->count();
            return response()->json(['individuals' => $members, 'organizations' => $organizations, 'total_projects' => $totalProjects], 200);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function storeTeamFinance(Request $request)
    {
        $request->validate(['user_id' => 'required', 'company_id' => 'required', 'amount' => 'required|numeric', 'type' => 'required']);
        
        // Pengecekan Relasi Komposit project_companies
        if ($request->project_id) {
            $isValid = DB::table('project_companies')
                ->where('project_id', $request->project_id)
                ->where('company_id', $request->company_id)
                ->exists();
            if (!$isValid) return response()->json(['error' => 'Relasi Project dan PT tidak ditemukan.'], 422);
        }

        DB::table('team_finances')->insert([
            'user_id' => $request->user_id, 'company_id' => $request->company_id, 'project_id' => $request->project_id ?: null,
            'amount' => $request->amount, 'type' => $request->type, 'description' => $request->description,
            'status' => 'Pending', 'transaction_date' => now(), 'created_at' => now()
        ]);
        return response()->json(['message' => 'Finance Record Added']);
    }

    public function getTopOutstanding()
    {
        $data = DB::table('team_finances')
            ->join('users', 'team_finances.user_id', '=', 'users.id')
            ->leftJoin('projects', 'team_finances.project_id', '=', 'projects.id')
            ->select('users.name as member_name', 'projects.project_title', 'team_finances.amount', 'team_finances.id')
            ->where('team_finances.status', 'Pending')->where('team_finances.type', 'Kasbon')->orderBy('team_finances.amount', 'desc')->limit(3)->get();
        return response()->json($data);
    }

    public function getCompanies() { return response()->json(DB::table('companies')->orderBy('id', 'asc')->get()); }

    public function storeCompany(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255', 'legal_name' => 'required|string|max:255',
            'email' => 'nullable|email', 'phone' => 'nullable|string|max:20', 'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048'
        ]);

        try {
            $logoPath = $request->hasFile('logo') ? $request->file('logo')->store('companies', 'public_uploads') : null;
            DB::table('companies')->insert([
                'name' => $request->name, 'legal_name' => $request->legal_name, 'email' => $request->email,
                'phone' => $request->phone, 'address' => $request->address, 'description' => $request->description, 'logo_path' => $logoPath
            ]);
            return response()->json(['message' => 'Organization berhasil ditambahkan!'], 201);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal menambahkan Organization: ' . $e->getMessage()], 500); }
    }

    public function updateCompany(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255', 'email' => 'nullable|email', 'phone' => 'nullable|string|max:20', 'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048']);
        try {
            $data = ['name' => $request->name, 'legal_name' => $request->legal_name, 'address' => $request->address, 'email' => $request->email, 'phone' => $request->phone, 'description' => $request->description, 'updated_at' => now()];
            if ($request->hasFile('logo')) $data['logo_path'] = $request->file('logo')->store('companies', 'public_uploads');
            DB::table('companies')->where('id', $id)->update($data);
            return response()->json(['message' => 'Organization berhasil diperbarui!'], 200);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal memperbarui PT: ' . $e->getMessage()], 500); }
    }

    public function destroyCompany($id)
    {
        try {
            DB::table('users')->where('company_id', $id)->update(['company_id' => null]);
            DB::table('companies')->where('id', $id)->delete();
            return response()->json(['message' => 'Organization berhasil dihapus!'], 200);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal menghapus PT: ' . $e->getMessage()], 500); }
    }

    public function getConsolidatedFinance(Request $request)
    {
        try {
            $ptId = $request->query('pt_id');
            $queryRevenue = DB::table('project_invoices')->where('status', 'Paid');
            if ($ptId && $ptId !== 'all') {
                $queryRevenue->whereExists(function($q) use ($ptId) {
                    $q->select(DB::raw(1))->from('project_companies')->whereColumn('project_companies.project_id', 'project_invoices.project_id')->where('project_companies.company_id', $ptId);
                });
            }
            $totalRevenue = $queryRevenue->sum('amount');

            $queryPurchasing = DB::table('project_purchasings');
            $queryWorkorders = DB::table('work_orders');
            
            if ($ptId && $ptId !== 'all') {
                $queryPurchasing->whereExists(function($q) use ($ptId) {
                    $q->select(DB::raw(1))->from('project_companies')->whereColumn('project_companies.project_id', 'project_purchasings.project_id')->where('project_companies.company_id', $ptId);
                });
                $queryWorkorders->whereExists(function($q) use ($ptId) {
                    $q->select(DB::raw(1))->from('project_companies')->whereColumn('project_companies.project_id', 'work_orders.project_id')->where('project_companies.company_id', $ptId);
                });
            }
            
            $totalExpenses = $queryPurchasing->sum('total_price') + $queryWorkorders->sum('budget');
            $totalReceivable = DB::table('project_invoices')->where('status', 'Unpaid')->sum('amount');

            $ptPerformance = DB::table('companies')
                ->select('id', 'name')
                ->get()
                ->map(function($pt) {
                    $projectIds = DB::table('project_companies')->where('company_id', $pt->id)->pluck('project_id');
                    $rev = DB::table('project_invoices')->whereIn('project_id', $projectIds)->where('status', 'Paid')->sum('amount');
                    $exp = DB::table('project_purchasings')->whereIn('project_id', $projectIds)->sum('total_price') 
                         + DB::table('work_orders')->whereIn('project_id', $projectIds)->sum('budget');
                    
                    return [
                        'id' => $pt->id, 'name' => $pt->name, 'project_count' => $projectIds->count(),
                        'revenue' => (float)$rev, 'expense' => (float)$exp, 'profit' => (float)($rev - $exp),
                        'margin' => $rev > 0 ? round((($rev - $exp) / $rev) * 100, 1) : 0
                    ];
                });

            return response()->json([
                'total_revenue' => (float)$totalRevenue, 'total_expense' => (float)$totalExpenses, 'total_receivable' => (float)$totalReceivable,
                'net_profit' => (float)($totalRevenue - $totalExpenses), 'pt_performance' => $ptPerformance, 'active_projects_count' => DB::table('projects')->where('status', '!=', 'Finish')->count()
            ]);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function getPTPerformance()
    {
        try {
            $performance = DB::table('companies')
                ->select('id', 'name')
                ->get()
                ->map(function($pt) {
                    $projectIds = DB::table('project_companies')->where('company_id', $pt->id)->pluck('project_id');
                    $revenue = DB::table('project_invoices')->whereIn('project_id', $projectIds)->where('status', 'Paid')->sum('amount');
                    $expense = DB::table('project_purchasings')->whereIn('project_id', $projectIds)->sum('total_price') 
                             + DB::table('work_orders')->whereIn('project_id', $projectIds)->sum('budget');

                    return [
                        'id' => $pt->id, 'name' => $pt->name, 'project_count' => $projectIds->count(),
                        'revenue' => (float)$revenue, 'expense' => (float)$expense, 'profit' => (float)($revenue - $expense)
                    ];
                });
            return response()->json($performance);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function getCOAs(Request $request)
    {
        // Join tabel projects agar vue bisa membaca nama/judul projectnya
        $query = DB::table('accounting_coas')
            ->leftJoin('projects', 'accounting_coas.project_id', '=', 'projects.id')
            ->select('accounting_coas.*', 'projects.project_title');

        if ($request->has('pt_id') && $request->pt_id !== 'all') {
            if ($request->pt_id === 'personal') {
                $query->whereNull('accounting_coas.pt_id');
            } else {
                $query->where('accounting_coas.pt_id', $request->pt_id);
            }
        }
        return response()->json($query->orderBy('accounting_coas.code', 'asc')->get());
    }

    public function storeCOA(Request $request)
    {
        $request->validate(['code' => 'required', 'name' => 'required', 'category' => 'required']);
        try {
            DB::table('accounting_coas')->insert([
                'pt_id' => $request->pt_id ?: null,
                'project_id' => $request->project_id ?: null, // <--- TAMBAHAN
                'code' => $request->code,
                'name' => $request->name,
                'category' => $request->category,
                'created_at' => now()
            ]);
            return response()->json(['message' => 'COA Account Created']);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function updateCOA(Request $request, $id)
    {
        $request->validate(['code' => 'required', 'name' => 'required', 'category' => 'required']);
        try {
            DB::table('accounting_coas')->where('id', $id)->update([
                'pt_id' => $request->pt_id ?: null,
                'project_id' => $request->project_id ?: null, // <--- TAMBAHAN
                'code' => $request->code,
                'name' => $request->name,
                'category' => $request->category,
                'updated_at' => now()
            ]);
            return response()->json(['message' => 'Akun COA berhasil diperbarui!']);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal memperbarui COA: ' . $e->getMessage()], 500); }
    }

    public function deleteCOA($id)
    {
        try {
            DB::table('accounting_coas')->where('id', $id)->delete();
            return response()->json(['message' => 'Akun COA berhasil dihapus!']);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal: Akun ini terkait transaksi.'], 500); }
    }

    public function getJournals(Request $request)
    {
        $query = DB::table('accounting_journals')
            ->leftJoin('accounting_coas', 'accounting_journals.coa_id', '=', 'accounting_coas.id')
            ->select('accounting_journals.*', 'accounting_coas.name as coa_name', 'accounting_coas.code as coa_code');
        if ($request->has('pt_id') && $request->pt_id !== 'all') {
            $query->where('accounting_journals.pt_id', $request->pt_id);
        }
        return response()->json($query->orderBy('transaction_date', 'desc')->get());
    }

    public function updateInvoiceStatus(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $invoice = DB::table('project_invoices')->where('id', $id)->first();
            if (!$invoice) return response()->json(['message' => 'Invoice not found'], 404);

            $updateData = ['status' => $request->status, 'updated_at' => now()];
            if ($request->status === 'Paid') { 
                $updateData['paid_at'] = now(); 

                $project = DB::table('projects')->where('id', $invoice->project_id)->first();
                // Ambil relasi dari project_companies
                $linkedCompany = DB::table('project_companies')->where('project_id', $project->id)->first();
                $ptId = $linkedCompany ? $linkedCompany->company_id : $project->company_id;

                DB::table('accounting_journals')->insert([
                    'pt_id' => $ptId,
                    'project_id' => $project->id,
                    'coa_id' => 3, 
                    'transaction_date' => now(),
                    'description' => "PELUNASAN: " . $invoice->title,
                    'debit' => (float)$invoice->amount,
                    'credit' => 0,
                    'reference_type' => 'Invoice',
                    'created_at' => now()
                ]);
            }

            DB::table('project_invoices')->where('id', $id)->update($updateData);
            
            DB::commit();
            return response()->json(['message' => 'Payment Status Updated & Journaled']);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCashFlow(Request $request)
    {
        try {
            $ptId = $request->query('pt_id');
            $query = DB::table('accounting_journals')
                ->leftJoin('accounting_coas', 'accounting_journals.coa_id', '=', 'accounting_coas.id')
                ->leftJoin('projects', 'accounting_journals.project_id', '=', 'projects.id')
                ->leftJoin('companies', 'accounting_journals.pt_id', '=', 'companies.id')
                ->select(
                    'accounting_journals.*', 'accounting_coas.name as account_name',
                    'projects.project_title', 'companies.name as company_name'
                );

            if ($ptId && $ptId !== 'all') $query->where('accounting_journals.pt_id', $ptId);

            $history = $query->orderBy('transaction_date', 'desc')->limit(100)->get();
            $totalIn = (float)DB::table('accounting_journals')->where(fn($q) => ($ptId && $ptId !== 'all') ? $q->where('pt_id', $ptId) : $q)->sum('debit') ?: 0;
            $totalOut = (float)DB::table('accounting_journals')->where(fn($q) => ($ptId && $ptId !== 'all') ? $q->where('pt_id', $ptId) : $q)->sum('credit') ?: 0;

            return response()->json(['history' => $history, 'summary' => ['total_inflow' => $totalIn, 'total_outflow' => $totalOut, 'net_flow' => $totalIn - $totalOut]]);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function getTransactions(Request $request)
    {
        try {
            $query = DB::table('finance_transactions')
                ->leftJoin('projects', 'finance_transactions.project_id', '=', 'projects.id')
                ->leftJoin('accounting_coas', 'finance_transactions.coa_id', '=', 'accounting_coas.id')
                ->leftJoin('companies', 'finance_transactions.company_id', '=', 'companies.id')
                ->select(
                    'finance_transactions.*',
                    'projects.project_title as project_name',
                    'accounting_coas.name as coa_name',
                    'accounting_coas.code as coa_code',
                    'companies.name as company_name'
                );

            if ($request->has('status') && $request->status !== 'all') $query->where('finance_transactions.status', $request->status);
            if ($request->has('type') && $request->type !== 'all') $query->where('finance_transactions.type', $request->type);
            if ($request->has('project_id') && $request->project_id !== 'all') $query->where('finance_transactions.project_id', $request->project_id);

            $transactions = $query->orderBy('transaction_date', 'desc')->get();
            return response()->json($transactions, 200);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function storeTransaction(Request $request)
    {
        $request->validate([
            'type' => 'required|in:inflow,outflow',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'coa_id' => 'required',
            'method' => 'required',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120' // Max 5MB
        ]);

        try {
            $filePath = null;
            // PENTING: Gunakan public_uploads agar tersimpan di /public/uploads/finance_attachments
            if ($request->hasFile('attachment')) {
                $filePath = $request->file('attachment')->store('finance_attachments', 'public_uploads');
            }

            $trxNumber = 'TRX-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));

            $id = DB::table('finance_transactions')->insertGetId([
                'transaction_number' => $trxNumber,
                'transaction_date' => $request->date,
                'ref_number' => $request->ref_number,
                'type' => $request->type,
                'project_id' => $request->project_id ?: null,
                'company_id' => $request->company_id ?: null,
                'coa_id' => $request->coa_id,
                'method' => $request->method,
                'bank_from' => $request->bank_from,
                'bank_to' => $request->bank_to,
                'amount' => $request->amount,
                'description' => $request->description,
                'attachment_path' => $filePath, // Nilai ini akan berisi path seperti "finance_attachments/xyz.pdf"
                'status' => 'Pending',
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['message' => 'Transaksi & Dokumen berhasil disimpan!', 'id' => $id], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal simpan: ' . $e->getMessage()], 500);
        }
    }

  public function updateTransaction(Request $request, $id)
{
    // 1. Validasi harus menyertakan attachment
    $request->validate([
        'type' => 'required|in:inflow,outflow',
        'date' => 'required|date',
        'amount' => 'required|numeric',
        'coa_id' => 'required',
        'method' => 'required',
        'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
    ]);

    try {
        $data = [
            'transaction_date' => $request->date,
            'ref_number' => $request->ref_number,
            'type' => $request->type,
            'project_id' => $request->project_id ?: null,
            'company_id' => $request->company_id ?: null,
            'coa_id' => $request->coa_id,
            'method' => $request->method,
            'bank_from' => $request->bank_from,
            'bank_to' => $request->bank_to,
            'amount' => $request->amount,
            'description' => $request->description,
            'updated_at' => now()
        ];

        // 2. LOGIKA KRUSIAL: Jika ada file, simpan dan masukkan ke array $data
        if ($request->hasFile('attachment')) {
            // Gunakan store untuk mendapatkan path string yang benar
            $data['attachment_path'] = $request->file('attachment')->store('finance_attachments', 'public_uploads');
        }

        // 3. Pastikan kolom tidak terupdate menjadi '0' jika tidak ada upload baru
        DB::table('finance_transactions')->where('id', $id)->update($data);
        
        return response()->json(['message' => 'Update Sukses!']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    public function destroyTransaction($id)
    {
        try {
            DB::table('finance_transactions')->where('id', $id)->delete();
            return response()->json(['message' => 'Transaksi berhasil dihapus!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus data'], 500);
        }
    }   
    public function getBanks(Request $request)
    {
        $query = DB::table('finance_banks');
        if ($request->has('pt_id') && $request->pt_id !== 'all') {
            if ($request->pt_id === 'personal') {
                $query->whereNull('pt_id');
            } else {
                $query->where('pt_id', $request->pt_id);
            }
        }
        return response()->json($query->orderBy('id', 'desc')->get());
    }

    public function storeBank(Request $request)
    {
        $request->validate(['bank_name' => 'required', 'account_name' => 'required', 'account_number' => 'required']);
        try {
            DB::table('finance_banks')->insert(['pt_id' => $request->pt_id ?: null, 'bank_name' => $request->bank_name, 'account_name' => $request->account_name, 'account_number' => $request->account_number, 'branch_office' => $request->branch_office, 'created_at' => now(), 'updated_at' => now()]);
            return response()->json(['message' => 'Rekening Bank berhasil ditambahkan!']);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function updateBank(Request $request, $id)
    {
        try {
            DB::table('finance_banks')->where('id', $id)->update(['pt_id' => $request->pt_id ?: null, 'bank_name' => $request->bank_name, 'account_name' => $request->account_name, 'account_number' => $request->account_number, 'branch_office' => $request->branch_office, 'updated_at' => now()]);
            return response()->json(['message' => 'Rekening Bank berhasil diupdate!']);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500); }
    }

    public function deleteBank($id)
    {
        try {
            DB::table('finance_banks')->where('id', $id)->delete();
            return response()->json(['message' => 'Rekening Bank berhasil dihapus!']);
        } catch (\Exception $e) { return response()->json(['error' => 'Gagal menghapus, data mungkin sedang digunakan.'], 500); }
    }
}
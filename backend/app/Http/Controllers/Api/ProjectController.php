<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class ProjectController extends Controller
{
    private function getTableName($type)
    {
        return match($type) {
            'categories' => 'work_categories',
            'status'     => 'work_statuses',
            'priority'   => 'work_priorities',
            'package'    => 'work_packages',
            'color'      => 'work_colors',
            'projects'   => 'projects',
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
                    DB::raw("(finish_date - start_date) as total_day_diff") 
                );

            // Fitur Drill-down: Filter berdasarkan Perusahaan jika ada parameter company_id
            if ($request->has('company_id')) {
                $query->where('projects.company_id', $request->company_id);
            }

            $projects = $query->orderBy('projects.created_at', 'desc')->get();

            $formatted = $projects->map(function($p) {
                return [
                    'id' => $p->id,
                    'project_title' => $p->project_title,
                    'client_name' => $p->client_name ?? '-',
                    'start_date' => $p->start_date ? Carbon::parse($p->start_date)->format('d F Y') : '-',
                    'finish_date' => $p->finish_date ? Carbon::parse($p->finish_date)->format('d F Y') : '-',
                    'total_day' => ($p->total_day_diff ?? 0) . ' Day',
                    'category' => $p->category_name,
                    'logo' => $p->category_logo ? 'uploads/' . $p->category_logo : null,
                    'status' => $p->status,
                    'priority' => $p->priority,
                    'package' => $p->package ?? '-',
                    'progress' => $p->progress_percent,
                    'contract_value' => (float)$p->contract_value,
                    'company_id' => $p->company_id,
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
            'category_id' => 'required|exists:work_categories,id',
            'company_id' => 'required|exists:companies,id' // Pastikan project terikat ke PT
        ]);

        try {
            $id = DB::table('projects')->insertGetId([
                'category_id' => $request->category_id,
                'company_id' => $request->company_id,
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
        // 1. Ambil Data Utama Project, Kategori, dan Nama PT Afiliasi
        $project = DB::table('projects')
            ->leftJoin('work_categories', 'projects.category_id', '=', 'work_categories.id')
            ->leftJoin('companies', 'projects.company_id', '=', 'companies.id') // RELASI KE PT
            ->select(
                'projects.*', 
                'work_categories.name as category_name', 
                'work_categories.image_path as logo',
                'companies.name as affiliated_pt_name' // Tampilkan nama PT
            )
            ->where('projects.id', $id)
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        // 2. Kalkulasi Finansial Project (Real-time)
        $totalPurchasing = DB::table('project_purchasings')->where('project_id', $id)->sum('total_price');
        $totalWorkOrder = DB::table('work_orders')->where('project_id', $id)->sum('budget');
        
        $project->financials = [
            'total_expense' => (float)($totalPurchasing + $totalWorkOrder),
            'total_purchasing' => (float)$totalPurchasing,
            'total_work_order' => (float)$totalWorkOrder,
            'remaining_budget' => (float)($project->contract_value - ($totalPurchasing + $totalWorkOrder))
        ];

        // 3. Load Team & Hitung Kontribusi Task per Member
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

        // 4. Load Tasks & Timeline Logic
        $project->tasks = DB::table('project_tasks')
            ->where('project_id', $id)
            ->orderBy('id', 'asc')
            ->get();
            
        $project->days_left = $project->deadline ? Carbon::parse($project->deadline)->diffInDays(now(), false) : null;

        // 5. Load Financial Sources (Work Orders & Purchasing)
        $project->work_orders = DB::table('work_orders')->where('project_id', $id)->orderBy('id', 'desc')->get();
        $project->purchasings = DB::table('project_purchasings')
            ->leftJoin('users', 'project_purchasings.user_id', '=', 'users.id')
            ->where('project_purchasings.project_id', $id)
            ->select('project_purchasings.*', 'users.name as buyer_name')
            ->orderBy('purchase_date', 'desc')
            ->get();

        // 6. Load Deliverables & Files
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

        // 7. Load CRM & Support
        $project->marketings = DB::table('project_marketings')->where('project_id', $id)->get();
        $project->supports = DB::table('project_supports')
            ->leftJoin('users as reporters', 'project_supports.user_id', '=', 'reporters.id')
            ->leftJoin('users as assigned', 'project_supports.assigned_to', '=', 'assigned.id')
            ->where('project_supports.project_id', $id)
            ->select('project_supports.*', 'reporters.name as reporter_name', 'assigned.name as assigned_name')
            ->get();

        // 8. Load Invoices & Payment Summary
        $project->invoices = DB::table('project_invoices')
            ->where('project_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
            
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
            'categories' => DB::table('work_categories')->orderBy('id', 'asc')->get(),
            'status'     => DB::table('work_statuses')->orderBy('id', 'asc')->get(),
            'priority'   => DB::table('work_priorities')->orderBy('id', 'asc')->get(),
            'package'    => DB::table('work_packages')->orderBy('id', 'asc')->get(),
            'color'      => DB::table('work_colors')->orderBy('id', 'asc')->get(), // Tambahkan ini juga
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


    public function updateTask(Request $request, $id)
    {
        // Ini buat update dokumentasi (deskripsi)
        DB::table('project_tasks')->where('id', $id)->update([
            'description' => $request->description,
            'updated_at' => now()
        ]);
        return response()->json(['message' => 'Documentation Updated']);
    }
    public function deleteTask($id)
    {
        $task = DB::table('project_tasks')->where('id', $id)->first();
        if (!$task) return response()->json(['message' => 'Not found'], 404);
        
        $projectId = $task->project_id;
        DB::table('project_tasks')->where('id', $id)->delete();
        
        // Update progress project setelah dihapus
        $this->updateProjectProgress($projectId);
        
        return response()->json(['message' => 'Task deleted']);
    }
    // --- WORK ORDER MANAGEMENT ---

    public function getWorkOrders($projectId)
    {
        // Mengambil WO berdasarkan project_id
        $orders = DB::table('work_orders')
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($orders);
    }

    public function storeWorkOrder(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title'      => 'required|string',
            'budget'     => 'numeric'
        ]);

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
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateWorkOrder(Request $request, $id)
    {
        try {
            DB::table('work_orders')->where('id', $id)->update([
                'title'       => $request->title,
                'description' => $request->description,
                'pic_name'    => $request->pic_name,
                'budget'      => $request->budget,
                'status'      => $request->status,
                'updated_at'  => now(),
            ]);

            return response()->json(['message' => 'Work Order Updated']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteWorkOrder($id)
    {
        try {
            DB::table('work_orders')->where('id', $id)->delete();
            return response()->json(['message' => 'Work Order Deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus Work Order'], 500);
        }
    }
    public function addTeamMember(Request $request, $id)
    {
        $request->validate(['user_id' => 'required', 'role' => 'required']);
        
        DB::table('project_teams')->insert([
            'project_id' => $id,
            'user_id' => $request->user_id,
            'role' => $request->role,
            'created_at' => now()
        ]);
        
        return response()->json(['message' => 'Member added']);
    }

    public function removeTeamMember($projectId, $userId)
    {
        DB::table('project_teams')
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->delete();
            
        return response()->json(['message' => 'Member removed']);
    }

public function storeProduction(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'title'      => 'required',
        'type'       => 'required',
        'content'    => 'required'
    ]);

    // Gunakan Auth::id() untuk memperbaiki error P1013
    DB::table('project_productions')->insert([
        'project_id' => $request->project_id,
        'user_id'    => Auth::id(), 
        'title'      => $request->title,
        'type'       => $request->type,
        'content'    => $request->content,
        'version'    => $request->version ?? '1.0.0',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json(['message' => 'Product Deliverable Saved']);
}
public function storeDocument(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'title'      => 'required|string|max:255',
        'file'       => 'required|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240', // Max 10MB
    ]);

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        // Simpan ke folder public/uploads/documents
        $path = $file->store('documents', 'public_uploads');

        DB::table('project_documents')->insert([
            'project_id' => $request->project_id,
            'user_id'    => Auth::id(),
            'title'      => $request->title,
            'file_path'  => $path,
            'file_type'  => $file->getClientOriginalExtension(),
            'file_size'  => $file->getSize(),
            'description'=> $request->description,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'Document Uploaded Successfully']);
    }

    return response()->json(['message' => 'No file uploaded'], 400);
}

public function deleteDocument($id)
{
    $doc = DB::table('project_documents')->where('id', $id)->first();
    if ($doc) {
        // Hapus file fisik jika perlu
        // Storage::disk('public_uploads')->delete($doc->file_path);
        
        DB::table('project_documents')->where('id', $id)->delete();
        return response()->json(['message' => 'Document Deleted']);
    }
    return response()->json(['message' => 'Document not found'], 404);
}
public function storeSupport(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'subject'    => 'required|string|max:255',
        'priority'   => 'required',
        'message'    => 'required'
    ]);

    DB::table('project_supports')->insert([
        'project_id'  => $request->project_id,
        'user_id'     => Auth::id(),
        'assigned_to' => $request->assigned_to, // Bisa null di awal
        'subject'     => $request->subject,
        'priority'    => $request->priority,
        'status'      => 'Open',
        'message'     => $request->message,
        'created_at'  => now(),
        'updated_at'  => now()
    ]);

    return response()->json(['message' => 'Support Ticket Created']);
}

public function updateSupportStatus(Request $request, $id)
{
    DB::table('project_supports')->where('id', $id)->update([
        'status'     => $request->status,
        'updated_at' => now()
    ]);

    return response()->json(['message' => 'Ticket Status Updated']);
}

// --- MARKETING MODULE ---

public function storeMarketing(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'title'      => 'required|string',
        'type'       => 'required',
    ]);

    DB::table('project_marketings')->insert([
        'project_id'      => $request->project_id,
        'user_id'         => Auth::id(),
        'title'           => $request->title,
        'type'            => $request->type,
        'budget_estimate' => $request->budget_estimate ?? 0,
        'next_follow_up'  => $request->next_follow_up,
        'status'          => 'Leads',
        'notes'           => $request->notes,
        'created_at'      => now(),
        'updated_at'      => now()
    ]);

    return response()->json(['message' => 'Marketing Lead Created']);
}
public function storePurchasing(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'item_name'  => 'required|string',
        'amount'     => 'required|numeric',
        'quantity'   => 'required|numeric',
    ]);

    try {
        DB::beginTransaction();

        $totalPrice = $request->amount * $request->quantity;
        $project = DB::table('projects')->where('id', $request->project_id)->first();

        // 1. Simpan ke tabel Purchasing
        $purId = DB::table('project_purchasings')->insertGetId([
            'project_id'   => $request->project_id,
            'user_id'      => Auth::id(),
            'item_name'    => $request->item_name,
            'vendor_name'  => $request->vendor_name,
            'amount'       => $request->amount,
            'quantity'     => $request->quantity,
            'total_price'  => $totalPrice,
            'purchase_date'=> $request->purchase_date ?? now(),
            'status'       => 'Paid', // Langsung dianggap Paid untuk operasional
            'created_at'   => now(),
            'updated_at'   => now()
        ]);

        // 2. LOGIKA OTOMATIS JURNAL: Catat Pengeluaran
        DB::table('accounting_journals')->insert([
            'pt_id' => $project->company_id,
            'project_id' => $project->id,
            'coa_id' => 4, // ID COA untuk Beban Operasional
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
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
public function deletePurchasing($id)
{
    try {
        DB::table('project_purchasings')->where('id', $id)->delete();
        return response()->json(['message' => 'Purchase record deleted']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Gagal menghapus data belanja'], 500);
    }
}
// --- ACCOUNTING / INVOICE MODULE ---

public function storeInvoice(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'title'      => 'required',
        'amount'     => 'required|numeric',
        'due_date'   => 'required|date'
    ]);

    // Auto-generate invoice number: INV-PROJECTID-TIMESTAMP
    $invNumber = 'INV-' . $request->project_id . '-' . time();

    DB::table('project_invoices')->insert([
        'project_id'     => $request->project_id,
        'invoice_number' => $invNumber,
        'title'          => $request->title,
        'amount'         => $request->amount,
        'due_date'       => $request->due_date,
        'status'         => 'Unpaid',
        'created_at'     => now(),
        'updated_at'     => now()
    ]);

    return response()->json(['message' => 'Invoice Created Successfully']);
}


public function getTeamworkSummary()
{
    try {
        // Ambil individu dengan info PT-nya dan total kasbon (outstanding)
        $members = DB::table('users')
            ->leftJoin('companies', 'users.company_id', '=', 'companies.id')
            ->leftJoin('team_finances', function($join) {
                $join->on('users.id', '=', 'team_finances.user_id')
                     ->where('team_finances.status', '=', 'Pending');
            })
            ->select(
                'users.id',
                'users.name',
                'users.role',
                'users.position',
                'companies.name as pt_owner_name',
                DB::raw('SUM(team_finances.amount) as outstanding')
            )
            ->groupBy('users.id', 'users.name', 'users.role', 'users.position', 'companies.name')
            ->get();

        // Ambil ringkasan Organisasi (PT/Vendor)
        $organizations = DB::table('companies')
            ->select('id', 'name', 'legal_name')
            ->get();
        $totalProjects = DB::table('projects')->count();
        return response()->json([
            'individuals' => $members,
            'organizations' => $organizations,
            'total_projects' => $totalProjects // Kirim angka asli ke frontend
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

// Fungsi untuk Team Leader input kebutuhan / kasbon
public function storeTeamFinance(Request $request)
{
    $request->validate([
        'user_id' => 'required',
        'company_id' => 'required',
        'amount' => 'required|numeric',
        'type' => 'required'
    ]);

    DB::table('team_finances')->insert([
        'user_id' => $request->user_id,
        'company_id' => $request->company_id,
        'amount' => $request->amount,
        'type' => $request->type,
        'description' => $request->description,
        'status' => 'Pending',
        'transaction_date' => now(),
        'created_at' => now()
    ]);

    // LOGIKA ACCOUNTING OTOMATIS: 
    // Di sini nanti bisa ditambahkan trigger untuk masuk ke tabel accounting_journals
    
    return response()->json(['message' => 'Finance Record Added']);
}
public function getTopOutstanding()
{
    // Mengambil top 3 kasbon tim yang masih pending
    $data = DB::table('team_finances')
        ->join('users', 'team_finances.user_id', '=', 'users.id')
        // Join ke projects untuk tahu duit ini dipakai di mana
        ->leftJoin('projects', 'team_finances.project_id', '=', 'projects.id')
        ->select(
            'users.name as member_name',
            'projects.project_title',
            'team_finances.amount',
            'team_finances.id'
        )
        ->where('team_finances.status', 'Pending')
        ->where('team_finances.type', 'Kasbon')
        ->orderBy('team_finances.amount', 'desc')
        ->limit(3)
        ->get();

    return response()->json($data);
}
    public function getCompanies()
    {
        return response()->json(DB::table('companies')->orderBy('id', 'asc')->get());
    }

// Tambahkan juga fungsi placeholder ini agar tidak error saat di-save
public function storeCompany(Request $request)
{
    $request->validate(['name' => 'required']);
    DB::table('companies')->insert([
        'name' => $request->name,
        'legal_name' => $request->legal_name,
        'created_at' => now()
    ]);
    return response()->json(['message' => 'Company created']);
}
    public function getConsolidatedFinance(Request $request)
    {
        try {
            $ptId = $request->query('pt_id');

            // 1. Revenue (Invoice Paid)
            $queryRevenue = DB::table('project_invoices')->where('status', 'Paid');
            if ($ptId && $ptId !== 'all') {
                $queryRevenue->whereIn('project_id', function($q) use ($ptId) {
                    $q->select('id')->from('projects')->where('company_id', $ptId);
                });
            }
            $totalRevenue = $queryRevenue->sum('amount');

            // 2. Expenses (Purchasing + Workorders)
            $queryPurchasing = DB::table('project_purchasings');
            $queryWorkorders = DB::table('work_orders');
            
            if ($ptId && $ptId !== 'all') {
                $queryPurchasing->whereIn('project_id', function($q) use ($ptId) {
                    $q->select('id')->from('projects')->where('company_id', $ptId);
                });
                $queryWorkorders->whereIn('project_id', function($q) use ($ptId) {
                    $q->select('id')->from('projects')->where('company_id', $ptId);
                });
            }
            
            $totalExpenses = $queryPurchasing->sum('total_price') + $queryWorkorders->sum('budget');
            $totalReceivable = DB::table('project_invoices')->where('status', 'Unpaid')->sum('amount');

            // 3. PT Performance Breakdown (Integrated)
            $ptPerformance = DB::table('companies')
                ->select('id', 'name')
                ->get()
                ->map(function($pt) {
                    $projectIds = DB::table('projects')->where('company_id', $pt->id)->pluck('id');
                    $rev = DB::table('project_invoices')->whereIn('project_id', $projectIds)->where('status', 'Paid')->sum('amount');
                    $exp = DB::table('project_purchasings')->whereIn('project_id', $projectIds)->sum('total_price') 
                         + DB::table('work_orders')->whereIn('project_id', $projectIds)->sum('budget');
                    
                    return [
                        'id' => $pt->id,
                        'name' => $pt->name,
                        'project_count' => $projectIds->count(),
                        'revenue' => (float)$rev,
                        'expense' => (float)$exp,
                        'profit' => (float)($rev - $exp),
                        'margin' => $rev > 0 ? round((($rev - $exp) / $rev) * 100, 1) : 0
                    ];
                });

            return response()->json([
                'total_revenue' => (float)$totalRevenue,
                'total_expense' => (float)$totalExpenses,
                'total_receivable' => (float)$totalReceivable,
                'net_profit' => (float)($totalRevenue - $totalExpenses),
                'pt_performance' => $ptPerformance,
                'active_projects_count' => DB::table('projects')->where('status', '!=', 'Finish')->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getPTPerformance()
    {
        try {
            $performance = DB::table('companies')
                ->select('id', 'name')
                ->get()
                ->map(function($pt) {
                    $projectIds = DB::table('projects')->where('company_id', $pt->id)->pluck('id');
                    $revenue = DB::table('project_invoices')->whereIn('project_id', $projectIds)->where('status', 'Paid')->sum('amount');
                    $expense = DB::table('project_purchasings')->whereIn('project_id', $projectIds)->sum('total_price') 
                             + DB::table('work_orders')->whereIn('project_id', $projectIds)->sum('budget');

                    return [
                        'id' => $pt->id,
                        'name' => $pt->name,
                        'project_count' => $projectIds->count(),
                        'revenue' => (float)$revenue,
                        'expense' => (float)$expense,
                        'profit' => (float)($revenue - $expense)
                    ];
                });

            return response()->json($performance);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCOAs(Request $request)
    {
        $query = DB::table('accounting_coas');
        if ($request->has('pt_id') && $request->pt_id !== 'all') {
            $query->where('pt_id', $request->pt_id);
        }
        return response()->json($query->orderBy('code', 'asc')->get());
    }

    public function storeCOA(Request $request)
    {
        $request->validate(['code' => 'required', 'name' => 'required', 'category' => 'required']);
        DB::table('accounting_coas')->insert([
            'pt_id' => $request->pt_id,
            'code' => $request->code,
            'name' => $request->name,
            'category' => $request->category,
            'header_id' => $request->header_id,
            'created_at' => now()
        ]);
        return response()->json(['message' => 'COA Account Created']);
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

            // AMBIL INFO PROJECT UNTUK TAHU PT MANA YANG PUNYA
            $project = DB::table('projects')->where('id', $invoice->project_id)->first();

            // LOGIKA OTOMATIS JURNAL: Catat Pendapatan
            DB::table('accounting_journals')->insert([
                'pt_id' => $project->company_id,
                'project_id' => $project->id,
                'coa_id' => 3, // ID COA untuk Pendapatan Project (Sesuai seeding kita tadi)
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
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
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
                'accounting_journals.*',
                'accounting_coas.name as account_name',
                'projects.project_title',
                'companies.name as company_name'
            );

        if ($ptId && $ptId !== 'all') {
            $query->where('accounting_journals.pt_id', $ptId);
        }

        $history = $query->orderBy('transaction_date', 'desc')->limit(100)->get();

        // Menggunakan SUM dengan casting float dan default 0
        $totalIn = (float)DB::table('accounting_journals')
            ->where(fn($q) => ($ptId && $ptId !== 'all') ? $q->where('pt_id', $ptId) : $q)
            ->sum('debit') ?: 0;

        $totalOut = (float)DB::table('accounting_journals')
            ->where(fn($q) => ($ptId && $ptId !== 'all') ? $q->where('pt_id', $ptId) : $q)
            ->sum('credit') ?: 0;

        return response()->json([
            'history' => $history,
            'summary' => [
                'total_inflow' => $totalIn,
                'total_outflow' => $totalOut,
                'net_flow' => $totalIn - $totalOut
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}
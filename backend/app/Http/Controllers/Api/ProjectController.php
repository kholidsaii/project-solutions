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
    // 1. Ambil Data Utama Project & Kategori
    $project = DB::table('projects')
        ->leftJoin('work_categories', 'projects.category_id', '=', 'work_categories.id')
        ->select('projects.*', 'work_categories.name as category_name', 'work_categories.image_path as logo')
        ->where('projects.id', $id)
        ->first();

    if (!$project) {
        return response()->json(['message' => 'Project tidak ditemukan'], 404);
    }

    // 2. Load Team & Hitung Kontribusi Task per Member (Relasi Aktivty)
    $project->team = DB::table('project_teams')
        ->join('users', 'project_teams.user_id', '=', 'users.id')
        ->where('project_teams.project_id', $id)
        ->select('users.id', 'users.name', 'project_teams.role')
        ->get();

    foreach ($project->team as $member) {
        $member->tasks_count = DB::table('project_tasks')
            ->where('project_id', $id)
            ->where('assigned_to', $member->id)
            ->count();
    }

    // 3. Load Aktivty (Tasks)
    $project->tasks = DB::table('project_tasks')
        ->where('project_id', $id)
        ->orderBy('id', 'asc')
        ->get();

    // 4. Load Work Orders (Financial Source A)
    $project->work_orders = DB::table('work_orders')
        ->where('project_id', $id)
        ->orderBy('id', 'desc')
        ->get();

    // 5. Load Purchasing (Financial Source B)
    $project->purchasings = DB::table('project_purchasings')
        ->leftJoin('users', 'project_purchasings.user_id', '=', 'users.id')
        ->where('project_purchasings.project_id', $id)
        ->select('project_purchasings.*', 'users.name as buyer_name')
        ->orderBy('project_purchasings.purchase_date', 'desc')
        ->get();

    // 6. Load Productions (Deliverables)
    $project->productions = DB::table('project_productions')
        ->leftJoin('users', 'project_productions.user_id', '=', 'users.id')
        ->where('project_productions.project_id', $id)
        ->select('project_productions.*', 'users.name as user_name')
        ->orderBy('project_productions.created_at', 'desc')
        ->get();

    // 7. Load Documents (Files)
    $project->documents = DB::table('project_documents')
        ->leftJoin('users', 'project_documents.user_id', '=', 'users.id')
        ->where('project_documents.project_id', $id)
        ->select('project_documents.*', 'users.name as uploader_name')
        ->orderBy('project_documents.created_at', 'desc')
        ->get();

    // 8. Load Marketing (CRM/Leads)
    $project->marketings = DB::table('project_marketings')
        ->leftJoin('users', 'project_marketings.user_id', '=', 'users.id')
        ->where('project_marketings.project_id', $id)
        ->select('project_marketings.*', 'users.name as marketer_name')
        ->orderBy('project_marketings.created_at', 'desc')
        ->get();

    // 9. Load Support (Ticketing) - INI YANG TADI TERLEWAT
    $project->supports = DB::table('project_supports')
        ->leftJoin('users as reporters', 'project_supports.user_id', '=', 'reporters.id')
        ->leftJoin('users as assigned', 'project_supports.assigned_to', '=', 'assigned.id')
        ->where('project_supports.project_id', $id)
        ->select('project_supports.*', 'reporters.name as reporter_name', 'assigned.name as assigned_name')
        ->orderBy('project_supports.created_at', 'desc')
        ->get();
    // Tambahkan di ProjectController.php fungsi show()
    $project->invoices = DB::table('project_invoices')
        ->where('project_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();
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

    DB::table('project_purchasings')->insert([
        'project_id'    => $request->project_id,
        'user_id'       => Auth::id(),
        'item_name'     => $request->item_name,
        'vendor_name'   => $request->vendor_name,
        'amount'        => $request->amount,
        'quantity'      => $request->quantity,
        'total_price'   => $request->amount * $request->quantity,
        'purchase_date' => $request->purchase_date ?? now(),
        'status'        => 'Pending',
        'notes'         => $request->notes,
        'created_at'    => now(),
        'updated_at'    => now()
    ]);

    return response()->json(['message' => 'Purchase Record Saved']);
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

public function updateInvoiceStatus(Request $request, $id)
{
    $updateData = [
        'status' => $request->status,
        'updated_at' => now()
    ];

    if ($request->status === 'Paid') {
        $updateData['paid_at'] = now();
    }

    DB::table('project_invoices')->where('id', $id)->update($updateData);

    return response()->json(['message' => 'Payment Status Updated']);
}
}
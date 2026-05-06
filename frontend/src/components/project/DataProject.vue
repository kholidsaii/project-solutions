<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios';

const router = useRouter();

// ==========================================
// 1. STATE MANAGEMENT
// ==========================================
const isLoading = ref(true);
const workData = ref<any[]>([]);
const allCompanies = ref<any[]>([]);
const activeFilter = ref({ type: 'all', value: 'all' as any });
const openMenu = ref<string | null>('status');

// Modal States
const showProjectModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

const allMasterData = ref({
  categories: [] as any[],
  package: [] as any[],
  priority: [] as any[],
  status: [] as any[]
});

// Form State (Mendukung File Upload & Many-to-Many Companies)
const logoPreview = ref<string | null>(null);
const projectForm = ref({
  project_title: '',
  client_name: '',
  contract_value: 0,
  deadline: '',
  category_id: null as number | null,
  company_ids: [] as number[],
  description: '',
  status: 'Planning',
  priority: 'Medium',
  package: '',
  logo: null as File | null
});

// ==========================================
// 2. HELPER & FORMATTING
// ==========================================
const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const formatDate = (dateStr: any) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const getImageUrl = (path: string | null): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith('data:') || path.startsWith('http')) return path;
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'; 
  const baseUrl = apiUrl.replace('/api', '');
  let cleanPath = path.startsWith('/') ? path.substring(1) : path;
  return cleanPath.toLowerCase().startsWith('uploads/') ? `${baseUrl}/${cleanPath}` : `${baseUrl}/uploads/${cleanPath}`;
};

// ==========================================
// 3. FILTERING LOGIC
// ==========================================
const setFilter = (type: string, value: string) => {
  activeFilter.value = { type, value };
};

const toggleMenu = (menuName: string) => {
  openMenu.value = openMenu.value === menuName ? null : menuName;
};

const filteredWorkData = computed(() => {
  if (activeFilter.value.type === 'all') return workData.value;
  return workData.value.filter(project => {
    const { type, value } = activeFilter.value;
    if (type === 'category') return project.category_name === value;
    if (type === 'status') return project.status === value;
    if (type === 'priority') return project.priority === value;
    if (type === 'package') return project.package === value;
    return true;
  });
});

const viewProjectDetail = (id: number | string) => {
  if (!id) return;
  router.push(`/projects/${id}`);
};

// ==========================================
// 4. API ACTIONS
// ==========================================
const fetchData = async () => {
  isLoading.value = true;
  try {
    const [resProjects, resComp, resMaster] = await Promise.all([
      api.get('/projects'),
      api.get('/companies'),
      api.get('/get-master-data')
    ]);
    
    workData.value = resProjects.data;
    allCompanies.value = resComp.data;
    if(resMaster.data) allMasterData.value = resMaster.data;
  } catch (e) {
    console.error("Gagal load data", e);
  } finally {
    isLoading.value = false;
  }
};

// ==========================================
// 5. CRUD OPERATIONS
// ==========================================
const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  logoPreview.value = null;
  projectForm.value = {
    project_title: '', client_name: '', contract_value: 0, deadline: '', 
    category_id: allMasterData.value.categories[0]?.id || null, 
    company_ids: [], description: '', status: 'Planning', priority: 'Medium', package: '', logo: null
  };
  showProjectModal.value = true;
};

const handleLogoChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    projectForm.value.logo = file;
    logoPreview.value = URL.createObjectURL(file);
  }
};

const handleEditProject = async (project: any) => {
  isEditing.value = true;
  editingId.value = project.id;
  
  try {
    const res = await api.get(`/projects/${project.id}`);
    const detail = res.data;
    const selectedCompanyIds = detail.companies ? detail.companies.map((c: any) => c.id) : [];

    projectForm.value = {
      project_title: detail.project_title || '',
      client_name: detail.client_name || '',
      contract_value: detail.contract_value || 0,
      deadline: detail.deadline ? detail.deadline.split(' ')[0] : '',
      category_id: detail.category_id || null,
      company_ids: selectedCompanyIds.length ? selectedCompanyIds : (detail.company_id ? [detail.company_id] : []),
      description: detail.description || '',
      status: detail.status || 'Planning',  
      priority: detail.priority || 'Medium',
      package: detail.package || '',
      logo: null
    };
    
    logoPreview.value = detail.logo ? (getImageUrl(detail.logo) || null) : null;
    showProjectModal.value = true;
  } catch (e) {
    console.error("Gagal mengambil detail untuk edit", e);
  }
};

const handleSaveProject = async () => {
  if (!projectForm.value.project_title || projectForm.value.company_ids.length === 0 || !projectForm.value.category_id) {
    return alert("Judul Proyek, Kategori, dan PT wajib diisi.");
  }

  try {
    const payload = new FormData();
    payload.append('project_title', projectForm.value.project_title);
    payload.append('client_name', projectForm.value.client_name);
    payload.append('contract_value', String(projectForm.value.contract_value));
    payload.append('deadline', projectForm.value.deadline);
    payload.append('start_date', projectForm.value.deadline);
    payload.append('category_id', String(projectForm.value.category_id));
    payload.append('company_id', String(projectForm.value.company_ids[0])); // Fallback legacy
    payload.append('description', projectForm.value.description);
    payload.append('status', projectForm.value.status);
    payload.append('priority', projectForm.value.priority);
    payload.append('package', projectForm.value.package);

    if (projectForm.value.logo) {
      payload.append('logo', projectForm.value.logo);
    }

    let projectId = editingId.value;

    if (isEditing.value) {
      payload.append('_method', 'PUT'); // Laravel Spoofer
      await api.post(`/projects/detail/${projectId}`, payload, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      const res = await api.post('/projects', payload, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      projectId = res.data.id;
    }

    if (projectId && projectForm.value.company_ids.length > 0) {
      await api.post(`/projects/${projectId}/sync-companies`, { company_ids: projectForm.value.company_ids });
    }

    showProjectModal.value = false;
    alert(isEditing.value ? 'Project diperbarui!' : 'Project dibuat!');
    await fetchData();

  } catch (e: any) {
    alert("Gagal menyimpan project: " + (e.response?.data?.message || "Error"));
  }
};

const handleDeleteProject = async (id: number) => {
  if (!confirm("Apakah Anda yakin ingin menghapus Project ini beserta seluruh data transaksinya?")) return;
  try {
    await api.delete(`/projects/${id}`);
    alert("Project berhasil dihapus!");
    await fetchData(); 
  } catch (error) {
    alert("Gagal menghapus data project. Project mungkin memiliki transaksi yang terikat.");
  }
};

onMounted(() => {
  fetchData();
});
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <!-- SIDEBAR NAVIGATION -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-[2rem] p-6 shadow-sm sticky top-8">
        <h4 class="text-[10px] font-black uppercase text-slate-400 mb-5 tracking-widest ml-2">Navigation</h4>
        <div class="space-y-1.5">
          <button @click="setFilter('all', 'all'); openMenu = null" class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl transition-all text-left group border" :class="activeFilter.type === 'all' ? 'bg-indigo-50 border-indigo-100 shadow-sm' : 'hover:bg-slate-50 border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors" :class="activeFilter.type === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-indigo-500'">
              <i class="fas fa-layer-group text-xs"></i>
            </div>
            <span class="text-[11px] font-black uppercase tracking-widest transition-colors" :class="activeFilter.type === 'all' ? 'text-indigo-600' : 'text-slate-500 group-hover:text-indigo-600'">All Works</span>
          </button>

          <div class="border rounded-2xl transition-all" :class="openMenu === 'status' || activeFilter.type === 'status' ? 'bg-slate-50 border-slate-100 pb-2' : 'border-transparent'">
            <button @click="toggleMenu('status')" class="w-full flex items-center justify-between px-4 py-3 rounded-2xl transition-all text-left group hover:bg-slate-50/80">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-blue-500" :class="activeFilter.type === 'status' ? 'bg-blue-100 text-blue-600' : ''"><i class="fas fa-tasks text-xs"></i></div>
                <span class="text-[11px] font-black uppercase tracking-widest transition-colors text-slate-500 group-hover:text-blue-600" :class="activeFilter.type === 'status' ? 'text-blue-600' : ''">All Status</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'status' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openMenu === 'status'" class="mt-1 px-2 space-y-1 animate-in slide-in-from-top-2 duration-300">
              <button v-for="s in ['Total', 'Finish', 'Progress', 'Planning', 'Pending']" :key="s" @click="setFilter('status', s)" class="w-full flex items-center text-left py-2.5 px-3 text-[10px] font-black uppercase tracking-widest transition-all rounded-xl" :class="activeFilter.value === s ? 'text-blue-600 bg-blue-100/50' : 'text-slate-400 hover:text-blue-500 hover:bg-slate-100/50'">
                <span class="opacity-40 font-normal mr-2 text-[11px]">#</span> {{ s }}
              </button>
            </div>
          </div>

          <div class="border rounded-2xl transition-all" :class="openMenu === 'priority' || activeFilter.type === 'priority' ? 'bg-slate-50 border-slate-100 pb-2' : 'border-transparent'">
            <button @click="toggleMenu('priority')" class="w-full flex items-center justify-between px-4 py-3 rounded-2xl transition-all text-left group hover:bg-slate-50/80">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-rose-500" :class="activeFilter.type === 'priority' ? 'bg-rose-100 text-rose-600' : ''"><i class="fas fa-fire-alt text-xs"></i></div>
                <span class="text-[11px] font-black uppercase tracking-widest transition-colors text-slate-500 group-hover:text-rose-600" :class="activeFilter.type === 'priority' ? 'text-rose-600' : ''">All Priority</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'priority' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openMenu === 'priority'" class="mt-1 px-2 space-y-1 animate-in slide-in-from-top-2 duration-300">
              <button v-for="p in ['Urgent', 'High', 'Medium', 'Low', 'Planned']" :key="p" @click="setFilter('priority', p)" class="w-full flex items-center text-left py-2.5 px-3 text-[10px] font-black uppercase tracking-widest transition-all rounded-xl" :class="activeFilter.value === p ? 'text-rose-600 bg-rose-100/50' : 'text-slate-400 hover:text-rose-500 hover:bg-slate-100/50'">
                <span class="opacity-40 font-normal mr-2 text-[11px]">#</span> {{ p }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT (Kanan) -->
    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px] relative">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-[3rem]"><i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>

      <!-- Top Bar -->
      <div class="bg-white rounded-[2rem] p-5 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4 relative z-10">
        <div class="flex items-center gap-4 w-full md:w-auto">
          <div class="bg-slate-50 border border-slate-100 text-indigo-700 text-[10px] font-bold uppercase py-2 px-5 rounded-xl flex items-center gap-3 min-w-[200px]">
            <span v-if="activeFilter.type === 'all'"><i class="fas fa-filter mr-2 opacity-50"></i> All Projects</span>
            <span v-else>{{ activeFilter.type }}: {{ activeFilter.value }}</span>
            <button v-if="activeFilter.type !== 'all'" @click="setFilter('all', 'all')" class="ml-auto text-rose-400 hover:text-rose-600 transition-colors"><i class="fas fa-times-circle text-sm"></i></button>
          </div>
        </div>
        <button @click="openCreateModal" class="w-full md:w-auto bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-md hover:bg-indigo-700 transition-all flex items-center justify-center gap-2">
          <i class="fas fa-plus"></i> Create Project
        </button>
      </div>

      <!-- Content Area (Card Persegi Panjang) -->
      <div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm flex-1 p-6 flex flex-col">
        <h3 class="text-sm font-black text-blue-900 mb-6 flex items-center gap-2">
          <i class="fas fa-th"></i> Works Detail
        </h3>
        
        <!-- Tambahkan shrink-0 pada children dan pastikan flex-col -->
        <div class="flex flex-col gap-4 overflow-y-auto custom-scrollbar pr-2 pb-4 flex-1">
          
          <div v-for="(project, index) in filteredWorkData" :key="project.id" @click="viewProjectDetail(project.id)"
            class="relative flex flex-col md:flex-row bg-white border border-slate-300 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-blue-400 transition-all cursor-pointer group flex-shrink-0">
            
            <!-- Warna List Kiri (Dinamic Warna warni persis Figma) -->
            <div class="w-3 flex-shrink-0" :class="['bg-[#4CAF50]', 'bg-[#1E3A8A]', 'bg-[#FFC107]', 'bg-[#9C27B0]'][index % 4]"></div>

            <!-- Bagian Kiri (Logo & ID) -->
            <div class="w-32 flex flex-col items-center justify-center p-4 border-r border-slate-200 bg-white">
              <div class="w-16 h-16 rounded-full overflow-hidden border border-slate-200 p-1 mb-2 bg-white flex items-center justify-center">
                <img v-if="project.logo" :src="getImageUrl(project.logo)" class="w-full h-full object-contain rounded-full">
                <div v-else class="w-full h-full bg-slate-50 rounded-full flex items-center justify-center text-slate-300">
                  <i class="fas fa-shield-alt text-2xl"></i>
                </div>
              </div>
              <span class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-3 py-0.5 rounded uppercase tracking-widest">
                ID-{{ project.id }}
              </span>
            </div>

            <!-- Bagian Tengah (Informasi Teks) -->
            <div class="flex-1 p-5 bg-white relative">
              <p class="text-[10px] text-slate-400 font-medium italic mb-1">
                Crate by : System {{ formatDate(project.created_at) }}
              </p>
              <h3 class="text-sm font-black text-blue-800 uppercase mb-4 pr-20">
                {{ project.project_title }}
              </h3>
              
              <!-- Grid Detail Data (2 Kolom persis Figma) -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-4 gap-y-2 text-[10px]">
                <!-- Kolom 1 -->
                <div class="space-y-1.5">
                   <div class="flex"><span class="w-24 font-bold text-slate-800">Customer</span><span class="truncate text-slate-600">: {{ project.client_name || '-' }}</span></div>
                   <div class="flex"><span class="w-24 font-bold text-slate-800">Start Date</span><span class="truncate text-slate-600">: {{ project.start_date || '-' }}</span></div>
                   <div class="flex"><span class="w-24 font-bold text-slate-800">Finish Date</span><span class="truncate text-slate-600">: {{ project.finish_date || '-' }}</span></div>
                   <div class="flex"><span class="w-24 font-bold text-slate-800">Total Day</span><span class="truncate text-slate-600">: {{ project.total_day_diff || 0 }} Day</span></div>
                </div>
                <!-- Kolom 2 -->
                <div class="space-y-1.5">
                   <div class="flex"><span class="w-28 font-bold text-slate-800">Work Category</span><span class="truncate text-slate-600">: {{ project.category || project.category_name || 'Project' }}</span></div>
                   <div class="flex"><span class="w-28 font-bold text-slate-800">Works Status</span><span class="truncate text-slate-600">: {{ project.status || 'Progress' }}</span></div>
                   <div class="flex"><span class="w-28 font-bold text-slate-800">Works Priority</span><span class="truncate text-slate-600">: {{ project.priority || 'Medium' }}</span></div>
                   <div class="flex"><span class="w-28 font-bold text-slate-800">Works Package</span><span class="truncate text-slate-600">: {{ project.package || '-' }}</span></div>
                </div>
              </div>

              <!-- Action Button Hover Overlay (Tombol Edit & Delete) -->
              <div class="absolute right-4 top-4 opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                 <button @click.stop="handleEditProject(project)" class="w-8 h-8 rounded bg-indigo-500 text-white shadow hover:bg-indigo-600 flex items-center justify-center">
                   <i class="fas fa-edit text-xs"></i>
                 </button>
                 <button @click.stop="handleDeleteProject(project.id)" class="w-8 h-8 rounded bg-rose-500 text-white shadow hover:bg-rose-600 flex items-center justify-center">
                   <i class="fas fa-trash text-xs"></i>
                 </button>
              </div>
            </div>

            <!-- Bagian Kanan (Progress Box Grey) -->
            <div class="w-32 bg-[#E6E9F0] flex flex-col flex-shrink-0 border-l border-slate-300">
              <div class="flex-1 flex flex-col items-center justify-center border-b border-slate-300 p-3">
                <span class="text-xl font-black text-blue-800">{{ project.progress || 0 }} %</span>
                <span class="text-[10px] font-bold text-blue-800 mt-1">Progress</span>
              </div>
              <div class="flex-1 flex flex-col items-center justify-center p-3">
                <span class="text-xl font-black text-blue-800">{{ project.team_count || 0 }}</span>
                <span class="text-[10px] font-bold text-blue-800 mt-1">Total Teamwork</span>
              </div>
            </div>

          </div>
          
          <div v-if="filteredWorkData.length === 0 && !isLoading" class="py-20 text-center opacity-40 border-2 border-dashed border-slate-200 rounded-[2rem]">
              <i class="fas fa-folder-open text-5xl mb-4 text-slate-300"></i>
              <p class="text-[11px] font-black uppercase tracking-widest text-slate-500">No Projects found</p>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL ADD / EDIT PROJECT -->
    <div v-if="showProjectModal" class="fixed inset-0 z-[999] flex items-center justify-center p-6">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showProjectModal = false"></div>
      <div class="bg-white w-full max-w-xl rounded-[2.5rem] shadow-2xl relative z-10 overflow-hidden animate-in zoom-in duration-300">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <div>
            <h3 class="text-xl font-black text-slate-800 uppercase italic">{{ isEditing ? 'Edit Project' : 'Create New Project' }}</h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Setup Workspace Details</p>
          </div>
          <button @click="showProjectModal = false" class="text-slate-300 hover:text-rose-500 transition-colors"><i class="fas fa-times-circle text-2xl"></i></button>
        </div>

        <div class="p-8 space-y-5 max-h-[70vh] overflow-y-auto custom-scrollbar">
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Project Title</label>
            <input v-model="projectForm.project_title" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100 transition-all uppercase">
          </div>
          <div class="grid grid-cols-2 gap-4">
             <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Client / Customer</label>
                <input v-model="projectForm.client_name" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
             </div>
             <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Contract Value (IDR)</label>
                <input v-model="projectForm.contract_value" type="number" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none">
             </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
             <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Deadline Date</label>
                <input v-model="projectForm.deadline" type="date" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none uppercase">
             </div>
             <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Category</label>
                <select v-model="projectForm.category_id" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none appearance-none cursor-pointer uppercase">
                  <option v-for="cat in allMasterData.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
             </div>
          </div>

          <div class="space-y-2 mt-4 bg-indigo-50/30 p-4 rounded-2xl border border-indigo-50">
            <label class="text-[9px] font-black text-indigo-500 uppercase ml-1"><i class="fas fa-building mr-1"></i> Affiliated Organizations / PT</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-40 overflow-y-auto custom-scrollbar">
              <label v-for="cp in allCompanies" :key="cp.id" class="flex items-center gap-3 p-2 hover:bg-white rounded-xl cursor-pointer transition-colors border border-transparent hover:border-indigo-100 shadow-sm">
                 <input type="checkbox" :value="cp.id" v-model="projectForm.company_ids" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                 <span class="text-[10px] font-bold text-slate-700 uppercase truncate">{{ cp.name }}</span>
              </label>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4 mt-4">
             <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Status</label>
                <select v-model="projectForm.status" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-[10px] font-bold outline-none uppercase">
                  <option value="Planning">Planning</option>
                  <option value="Progress">Progress</option>
                  <option value="Finish">Finish</option>
                </select>
             </div>
             <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Priority</label>
                <select v-model="projectForm.priority" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-[10px] font-bold outline-none uppercase">
                  <option value="Medium">Medium</option>
                  <option value="High">High</option>
                  <option value="Urgent">Urgent</option>
                </select>
             </div>
             <div class="space-y-1">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Package</label>
                <select v-model="projectForm.package" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-[10px] font-bold outline-none uppercase">
                  <option value="">- SELECT -</option>
                  <option v-for="pkg in allMasterData.package" :key="pkg.id" :value="pkg.name">{{ pkg.name }}</option>
                </select>
             </div>
          </div>

          <!-- INPUT LOGO PROYEK -->
          <div class="space-y-1 mt-4">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Project Logo / Image</label>
            <div class="relative w-full h-[80px] border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400 hover:bg-slate-50 transition-all cursor-pointer overflow-hidden group">
              <input type="file" @change="handleLogoChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/*">
              <img v-if="logoPreview" :src="logoPreview" class="absolute inset-0 w-full h-full object-contain p-1 opacity-50 z-0" />
              <i v-if="!logoPreview" class="fas fa-image text-2xl mb-1 group-hover:text-indigo-500"></i>
              <span class="text-[9px] font-black uppercase tracking-widest text-center px-4 relative z-10" :class="logoPreview ? 'text-indigo-600 bg-white/90 px-3 py-1 rounded shadow-sm' : ''">
                {{ projectForm.logo ? projectForm.logo.name : (logoPreview ? 'Change Image' : 'Upload Image') }}
              </span>
            </div>
          </div>
          
          <div class="space-y-1 mt-4">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Description</label>
            <textarea v-model="projectForm.description" rows="2" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs font-bold outline-none resize-none uppercase"></textarea>
          </div>
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100">
          <button @click="handleSaveProject" class="w-full bg-indigo-600 text-white py-4 rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] shadow-lg shadow-indigo-100 hover:bg-indigo-700 active:scale-95 transition-all">
            {{ isEditing ? 'Save Changes' : 'Confirm & Create Project' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>
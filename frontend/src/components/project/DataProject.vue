<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios';

const router = useRouter();

// ==========================================
// 1. GLOBAL STATE & MASTER DATA
// ==========================================
const isLoading = ref(true);
const workData = ref<any[]>([]);
const allCompanies = ref<any[]>([]);
const projectTags = ref<any[]>([]); 

// State Filter & Navigasi
const activeFilter = ref({ type: 'all', value: 'all' as any });
const openNavMenu = ref('status'); 
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = ref(5); 

const allMasterData = ref({
  categories: [] as any[],
  package: [] as any[],
  priority: [] as any[],
  status: [] as any[]
});

// ==========================================
// 2. MODAL & FORM STATE
// ==========================================
const showProjectModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

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
// 3. HELPER & FORMATTING
// ==========================================
const formatDate = (dateStr: any) => {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
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
// 4. FILTERING & PAGINATION LOGIC
// ==========================================
const setFilter = (type: string, value: string | number) => {
  activeFilter.value = { type, value };
};

watch([activeFilter, searchQuery], () => {
  currentPage.value = 1;
}, { deep: true });

const searchedAndFilteredData = computed(() => {
  let filtered = workData.value;

  if (activeFilter.value.type !== 'all') {
    filtered = filtered.filter(project => {
      const { type, value } = activeFilter.value;
      if (value === 'all') return true;

      if (type === 'tag') {
        const keyword = String(value).toLowerCase();
        const titleMatch = project.project_title ? project.project_title.toLowerCase().includes(keyword) : false;
        const clientMatch = project.client_name ? project.client_name.toLowerCase().includes(keyword) : false;
        const catMatch = project.category ? project.category.toLowerCase().includes(keyword) : false;
        return titleMatch || clientMatch || catMatch;
      }

      if (type === 'category') return project.category_name === value || project.category === value;
      if (type === 'status') return project.status === value;
      if (type === 'priority') return project.priority === value;
      if (type === 'package') return project.package === value;
      return true;
    });
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    filtered = filtered.filter(project => {
      return (
        (project.project_title && project.project_title.toLowerCase().includes(q)) ||
        (project.client_name && project.client_name.toLowerCase().includes(q)) ||
        (String(project.id).includes(q))
      );
    });
  }

  return filtered;
});

const totalPages = computed(() => Math.ceil(searchedAndFilteredData.value.length / itemsPerPage.value) || 1);
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return searchedAndFilteredData.value.slice(start, start + itemsPerPage.value);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const goToPage = (page: number) => { currentPage.value = page; };

const viewProjectDetail = (id: number | string) => {
  if (!id) return;
  router.push(`/projects/${id}`);
};

// ==========================================
// 5. API ACTIONS
// ==========================================
const fetchMasterData = async () => {
  try {
    const [resProjects, resComp, resMaster, resTags] = await Promise.all([
      api.get('/projects'),
      api.get('/companies'),
      api.get('/get-master-data'),
      api.get('/master-data/tags_works')
    ]);
    
    workData.value = resProjects.data;
    allCompanies.value = resComp.data;
    if(resMaster.data) allMasterData.value = resMaster.data;
    projectTags.value = resTags.data.data || resTags.data;
  } catch (e) {
    console.error("Gagal load data master", e);
  } finally {
    isLoading.value = false;
  }
};

const fetchDataOnly = async () => {
    try {
        const res = await api.get('/projects');
        workData.value = res.data;
    } catch (e) {
        console.error("Gagal load data project", e);
    }
}

// ==========================================
// 6. CRUD OPERATIONS
// ==========================================
const resetForm = () => {
  isEditing.value = false;
  editingId.value = null;
  logoPreview.value = null;
  projectForm.value = {
    project_title: '', client_name: '', contract_value: 0, deadline: '', 
    category_id: allMasterData.value.categories[0]?.id || null, 
    company_ids: [], description: '', status: 'Planning', priority: 'Medium', package: '', logo: null
  };
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
    payload.append('company_id', String(projectForm.value.company_ids[0])); 
    payload.append('description', projectForm.value.description);
    payload.append('status', projectForm.value.status);
    payload.append('priority', projectForm.value.priority);
    payload.append('package', projectForm.value.package);

    if (projectForm.value.logo) {
      payload.append('logo', projectForm.value.logo);
    }

    let projectId = editingId.value;

    if (isEditing.value) {
      payload.append('_method', 'PUT'); 
      await api.post(`/projects/detail/${projectId}`, payload, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert('Project berhasil diperbarui!');
    } else {
      const res = await api.post('/projects', payload, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      projectId = res.data.id;
      alert('Project berhasil dibuat!');
    }

    if (projectId && projectForm.value.company_ids.length > 0) {
      await api.post(`/projects/${projectId}/sync-companies`, { company_ids: projectForm.value.company_ids });
    }

    showProjectModal.value = false;
    await fetchDataOnly();
    resetForm();

  } catch (e: any) {
    alert("Gagal menyimpan project: " + (e.response?.data?.message || "Error"));
  }
};

const handleDeleteProject = async (id: number) => {
  if (!confirm("Apakah Anda yakin ingin menghapus Project ini beserta seluruh data transaksinya?")) return;
  try {
    await api.delete(`/projects/${id}`);
    alert("Project berhasil dihapus!");
    await fetchDataOnly(); 
  } catch (error) {
    alert("Gagal menghapus data project. Project mungkin memiliki transaksi yang terikat.");
  }
};

onMounted(() => {
  fetchMasterData();
});
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm sticky top-8">
        <h4 class="text-[13px] font-bold text-center mb-3 pb-3 border-b border-gray-100">Navigation</h4>
        
        <div class="space-y-1">
          <button @click="setFilter('all', 'all'); openNavMenu = ''" 
            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all text-[12px] font-medium"
            :class="activeFilter.type === 'all' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50'">
            <div class="w-5 text-center shrink-0"><i class="fas fa-layer-group" :class="activeFilter.type === 'all' ? 'text-blue-500' : 'text-gray-400'"></i></div>
            <span>All Projects</span>
          </button>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'status' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'status' ? '' : 'status'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-folder text-gray-400"></i></div>
                <span>Status</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'status' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'status'" class="px-2 pb-2 space-y-1">
              <button @click="setFilter('status', 'all')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'status' && activeFilter.value === 'all' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Semua Status</button>
              <button v-for="s in allMasterData.status" :key="s.id" @click="setFilter('status', s.name)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all capitalize" :class="activeFilter.type === 'status' && activeFilter.value === s.name ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ s.name }}</button>
            </div>
          </div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'priority' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'priority' ? '' : 'priority'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-folder text-gray-400"></i></div>
                <span>Priority</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'priority' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'priority'" class="px-2 pb-2 space-y-1">
              <button @click="setFilter('priority', 'all')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'priority' && activeFilter.value === 'all' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Semua Prioritas</button>
              <button v-for="p in allMasterData.priority" :key="p.id" @click="setFilter('priority', p.name)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'priority' && activeFilter.value === p.name ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ p.name }}</button>
            </div>
          </div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'category' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'category' ? '' : 'category'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-folder text-gray-400"></i></div>
                <span>Category</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'category' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'category'" class="px-2 pb-2 space-y-1 max-h-[200px] overflow-y-auto custom-scrollbar">
              <button @click="setFilter('category', 'all')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'category' && activeFilter.value === 'all' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Semua Kategori</button>
              <button v-for="cat in allMasterData.categories" :key="cat.id" @click="setFilter('category', cat.name)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'category' && activeFilter.value === cat.name ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ cat.name }}</button>
            </div>
          </div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'package' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'package' ? '' : 'package'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-folder text-gray-400"></i></div>
                <span>Package Project</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'package' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'package'" class="px-2 pb-2 space-y-1">
              <button @click="setFilter('package', 'all')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'package' && activeFilter.value === 'all' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Semua Paket</button>
              <button v-for="pkg in allMasterData.package" :key="pkg.id" @click="setFilter('package', pkg.name)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'package' && activeFilter.value === pkg.name ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ pkg.name }}</button>
            </div>
          </div>

          <div class="border-t border-gray-100 my-2"></div>

          <div class="rounded-lg transition-all" :class="openNavMenu === 'tag' ? 'bg-gray-50' : ''">
            <button @click="openNavMenu = openNavMenu === 'tag' ? '' : 'tag'" class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-[12px] font-medium text-gray-600 hover:bg-gray-100">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center shrink-0"><i class="fas fa-tags text-gray-400"></i></div>
                <span>Label Project</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] transition-transform" :class="openNavMenu === 'tag' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openNavMenu === 'tag'" class="px-2 pb-2 space-y-1 max-h-[200px] overflow-y-auto custom-scrollbar">
              <div v-if="projectTags.length === 0" class="text-[10px] text-gray-400 italic py-2 px-11">Belum ada label disetup</div>
              <template v-else>
                <button @click="setFilter('tag', 'all')" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'tag' && activeFilter.value === 'all' ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">Semua Label</button>
                <button v-for="tag in projectTags" :key="tag.id" @click="setFilter('tag', tag.name)" class="w-full text-left py-2 px-11 text-[11px] font-medium rounded-md transition-all" :class="activeFilter.type === 'tag' && activeFilter.value === tag.name ? 'text-blue-600 bg-blue-100/50' : 'text-gray-500 hover:bg-white'">{{ tag.name }}</button>
              </template>
            </div>
          </div>

        </div>
      </div>
    </div>
    
    <div class="lg:col-span-9 flex flex-col gap-4 min-h-[600px] relative">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl"><i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>

      <div class="bg-white border border-slate-200 rounded-lg p-2 flex flex-col md:flex-row justify-between items-center gap-4 relative z-10 shadow-sm">
        <div class="flex items-center gap-2 w-full px-2">
          <div class="bg-indigo-500 text-white px-4 py-2 rounded-md flex items-center gap-2 text-xs font-bold shadow-sm w-40 justify-center shrink-0">
            <i class="fas fa-project-diagram"></i> Data Project
          </div>
          <div class="relative flex-1">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
            <input v-model="searchQuery" type="text" placeholder="Search Project, Customer, ID..." class="w-full pl-8 pr-8 py-2 bg-white border border-slate-200 rounded-md text-xs font-medium outline-none focus:ring-1 ring-indigo-300 transition-colors">
            <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-rose-400 hover:text-rose-600">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <button class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-md flex items-center gap-2 text-xs font-bold hover:bg-slate-50 shrink-0">
            <i class="fas fa-filter text-indigo-500"></i> Filter
          </button>
          <div class="flex items-center gap-1 shrink-0">
            <button class="bg-indigo-500 text-white w-8 h-8 rounded-md flex items-center justify-center"><i class="fas fa-bars"></i></button>
            <button class="bg-indigo-500 text-white w-8 h-8 rounded-md flex items-center justify-center opacity-60 hover:opacity-100"><i class="fas fa-th-large"></i></button>
          </div>
          <button @click="resetForm(); showProjectModal = true" class="bg-indigo-500 text-white w-8 h-8 rounded-md flex items-center justify-center hover:bg-indigo-600 shadow-md shrink-0">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      </div>

      <div class="flex-1 space-y-3 mt-2">
        <div v-if="searchedAndFilteredData.length === 0 && !isLoading" class="p-10 text-center text-slate-400 border-2 border-dashed border-slate-200 rounded-xl bg-white">
           <i class="fas fa-folder-open text-4xl mb-3"></i>
           <p class="text-xs font-bold uppercase tracking-widest">{{ searchQuery ? 'Pencarian tidak ditemukan' : 'Belum ada data project' }}</p>
        </div>

        <div v-for="(project, index) in paginatedData" :key="project.id" @click="viewProjectDetail(project.id)"
          class="bg-white border border-slate-200 rounded-lg p-4 flex flex-col md:flex-row items-center gap-4 relative overflow-hidden group shadow-sm hover:shadow-md transition-all cursor-pointer">
          
          <div class="absolute left-0 top-0 bottom-0 w-2" :class="['bg-cyan-500', 'bg-rose-500', 'bg-indigo-500', 'bg-amber-500', 'bg-emerald-500', 'bg-purple-500'][index % 6]"></div>
          
          <div class="w-16 h-16 rounded-full border-2 border-slate-100 flex items-center justify-center flex-shrink-0 bg-slate-50 ml-2 overflow-hidden shadow-sm">
             <img v-if="project.logo" :src="getImageUrl(project.logo)" alt="Logo Project" class="w-full h-full object-cover">
             <i v-else class="fas fa-shield-alt text-2xl text-slate-300"></i>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap gap-2 mb-2">
              <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-rose-500 uppercase shadow-sm">Project</span>
              <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-emerald-500 uppercase shadow-sm">{{ project.package || 'Elektrikal' }}</span>
              <span class="px-3 py-1 rounded-full text-[10px] font-black text-slate-700 bg-yellow-300 uppercase shadow-sm">{{ project.status || 'Progress' }}</span>
              <span class="px-3 py-1 rounded-full text-[10px] font-black text-white bg-blue-600 uppercase shadow-sm">Team: {{ project.team_count || 0 }}</span>
            </div>
            
            <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight truncate">{{ project.project_title }}</h4>
            
            <div class="grid grid-cols-2 text-[10px] font-bold text-slate-500 mt-1">
              <div>Customer / PT: <span class="text-slate-700">{{ project.client_name || '-' }}</span></div>
              <div>Category: <span class="text-slate-700">{{ project.category || 'General' }}</span></div>
              <div>Start Date: <span class="text-slate-700">{{ formatDate(project.start_date) }}</span></div>
              <div>Finish Date: <span class="text-slate-700">{{ formatDate(project.finish_date) }}</span></div>
            </div>
            <p class="text-[9px] text-slate-400 mt-2">ID: PRJ-{{ project.id }} | Create by System</p>
          </div>

          <div class="flex flex-col items-end gap-3 flex-shrink-0">
            <div class="w-20 h-20 rounded-full flex flex-col items-center justify-center shadow-inner border-4 border-white ring-2 bg-emerald-50 ring-emerald-300">
               <span class="text-sm font-black text-center px-2 text-emerald-600">{{ project.progress || 0 }}%</span>
               <span class="text-[8px] font-bold text-slate-500 uppercase mt-1">Progress</span>
            </div>
            
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
               <button @click.stop="handleEditProject(project)" class="w-6 h-6 rounded bg-slate-100 text-indigo-600 hover:bg-indigo-500 hover:text-white flex items-center justify-center"><i class="fas fa-edit text-[10px]"></i></button>
               <button @click.stop="handleDeleteProject(project.id)" class="w-6 h-6 rounded bg-slate-100 text-rose-500 hover:bg-rose-500 hover:text-white flex items-center justify-center"><i class="fas fa-trash text-[10px]"></i></button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="totalPages > 1" class="pt-4 flex flex-col md:flex-row items-center justify-between gap-4">
        <span class="text-[11px] font-semibold text-slate-500">
          Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, searchedAndFilteredData.length) }} of {{ searchedAndFilteredData.length }} Data
        </span>
        <div class="flex items-center gap-1.5">
          <button @click="prevPage" :disabled="currentPage === 1" class="w-8 h-8 rounded-md flex items-center justify-center border border-slate-200 text-slate-500 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm">
            <i class="fas fa-chevron-left text-[10px]"></i>
          </button>
          <div class="flex items-center gap-1">
            <button v-for="page in totalPages" :key="page" @click="goToPage(page)" class="w-8 h-8 rounded-md flex items-center justify-center text-[11px] font-bold transition-all shadow-sm" :class="currentPage === page ? 'bg-indigo-500 text-white' : 'text-slate-600 bg-white border border-slate-200 hover:bg-slate-50'">
              {{ page }}
            </button>
          </div>
          <button @click="nextPage" :disabled="currentPage === totalPages" class="w-8 h-8 rounded-md flex items-center justify-center border border-slate-200 text-slate-500 bg-white hover:bg-slate-50 disabled:opacity-50 transition-all shadow-sm">
            <i class="fas fa-chevron-right text-[10px]"></i>
          </button>
        </div>
      </div>

    </div>

    <div v-if="showProjectModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showProjectModal = false"></div>
      
      <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl relative z-10 overflow-hidden animate-in zoom-in duration-300 flex flex-col max-h-[90vh]">
        
        <div class="px-8 pt-8 pb-4 flex justify-between items-start shrink-0">
          <div>
            <h3 class="text-2xl font-black text-[#2B3674] uppercase italic tracking-tight leading-none">
              {{ isEditing ? 'EDIT PROJECT' : 'CREATE NEW PROJECT' }}
            </h3>
            <p class="text-[10px] font-bold text-[#A3AED0] uppercase tracking-widest mt-2">Setup Workspace Details</p>
          </div>
          <button @click="showProjectModal = false" class="w-8 h-8 rounded-full bg-[#F4F7FE] text-[#A3AED0] hover:bg-rose-100 hover:text-rose-500 flex items-center justify-center transition-colors shrink-0">
            <i class="fas fa-times text-sm"></i>
          </button>
        </div>

        <div class="px-8 py-2 space-y-5 overflow-y-auto custom-scrollbar flex-1">
          
          <div class="space-y-2">
            <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Project Title</label>
            <input v-model="projectForm.project_title" type="text" class="w-full bg-[#F4F7FE] border-none rounded-2xl px-5 py-4 text-xs font-bold text-[#2B3674] outline-none focus:ring-2 focus:ring-[#4318FF] transition-all uppercase placeholder-[#A3AED0]">
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
             <div class="space-y-2">
                <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Client / Customer</label>
                <input v-model="projectForm.client_name" type="text" class="w-full bg-[#F4F7FE] border-none rounded-2xl px-5 py-4 text-xs font-bold text-[#2B3674] outline-none focus:ring-2 focus:ring-[#4318FF] transition-all uppercase placeholder-[#A3AED0]">
             </div>
             <div class="space-y-2">
                <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Contract Value (IDR)</label>
                <div class="relative">
                  <span class="absolute left-5 top-1/2 -translate-y-1/2 font-black text-[#A3AED0]">Rp</span>
                  <input v-model="projectForm.contract_value" type="number" class="w-full bg-[#F4F7FE] border-none rounded-2xl pl-12 pr-5 py-4 text-xs font-bold text-[#2B3674] outline-none focus:ring-2 focus:ring-[#4318FF] transition-all placeholder-[#A3AED0]" placeholder="0">
                </div>
             </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
             <div class="space-y-2">
                <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Deadline Date</label>
                <input v-model="projectForm.deadline" type="date" class="w-full bg-[#F4F7FE] border-none rounded-2xl px-5 py-4 text-xs font-bold text-[#2B3674] outline-none focus:ring-2 focus:ring-[#4318FF] transition-all uppercase text-[#A3AED0]">
             </div>
             <div class="space-y-2">
                <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Category</label>
                <div class="relative">
                  <select v-model="projectForm.category_id" class="w-full bg-[#F4F7FE] border-none rounded-2xl px-5 py-4 text-xs font-bold text-[#2B3674] outline-none focus:ring-2 focus:ring-[#4318FF] transition-all uppercase appearance-none cursor-pointer">
                    <option v-for="cat in allMasterData.categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                  </select>
                  <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-[#A3AED0] pointer-events-none text-[10px]"></i>
                </div>
             </div>
          </div>

          <div class="border-2 border-[#F4F7FE] p-6 rounded-[2rem] mt-2">
            <label class="flex items-center gap-2 text-[10px] font-black text-[#4318FF] uppercase tracking-widest mb-4">
              <i class="fas fa-bookmark text-[#4318FF]"></i> Affiliated Organizations / PT
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-40 overflow-y-auto custom-scrollbar pr-2">
              <label v-for="cp in allCompanies" :key="cp.id" class="flex items-center gap-3 p-3 bg-white border border-slate-100 rounded-xl cursor-pointer hover:border-[#4318FF] shadow-sm transition-all group">
                 <input type="checkbox" :value="cp.id" v-model="projectForm.company_ids" class="w-4 h-4 text-[#4318FF] rounded border-slate-200 focus:ring-[#4318FF]">
                 <span class="text-[10px] font-bold text-[#2B3674] uppercase truncate group-hover:text-[#4318FF] transition-colors">{{ cp.name }}</span>
              </label>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
             <div class="space-y-2 relative">
                <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Status</label>
                <div class="relative">
                  <select v-model="projectForm.status" class="w-full bg-[#F4F7FE] border-none rounded-2xl px-5 py-4 text-[11px] font-bold text-[#2B3674] outline-none uppercase appearance-none focus:ring-2 focus:ring-[#4318FF] cursor-pointer">
                    <option value="Planning">Planning</option>
                    <option value="Progress">Progress</option>
                    <option value="Finish">Finish</option>
                  </select>
                  <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-[#A3AED0] pointer-events-none text-[10px]"></i>
                </div>
             </div>
             <div class="space-y-2 relative">
                <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Priority</label>
                <div class="relative">
                  <select v-model="projectForm.priority" class="w-full bg-[#F4F7FE] border-none rounded-2xl px-5 py-4 text-[11px] font-bold text-[#2B3674] outline-none uppercase appearance-none focus:ring-2 focus:ring-[#4318FF] cursor-pointer">
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Urgent">Urgent</option>
                  </select>
                  <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-[#A3AED0] pointer-events-none text-[10px]"></i>
                </div>
             </div>
             <div class="space-y-2 relative">
                <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Package</label>
                <div class="relative">
                  <select v-model="projectForm.package" class="w-full bg-[#F4F7FE] border-none rounded-2xl px-5 py-4 text-[11px] font-bold text-[#2B3674] outline-none uppercase appearance-none focus:ring-2 focus:ring-[#4318FF] cursor-pointer">
                    <option value="">- SELECT -</option>
                    <option v-for="pkg in allMasterData.package" :key="pkg.id" :value="pkg.name">{{ pkg.name }}</option>
                  </select>
                  <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-[#A3AED0] pointer-events-none text-[10px]"></i>
                </div>
             </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-2 pb-4">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Description</label>
              <textarea v-model="projectForm.description" rows="3" class="w-full bg-[#F4F7FE] border-none rounded-2xl px-5 py-4 text-xs font-bold text-[#2B3674] outline-none resize-none uppercase focus:ring-2 focus:ring-[#4318FF]" placeholder="BRIEF DESCRIPTION..."></textarea>
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black text-[#8F9BBA] uppercase tracking-widest">Project Logo / Image</label>
              <div class="relative w-full h-[95px] bg-[#F4F7FE] border-2 border-dashed border-[#A3AED0] rounded-2xl flex flex-col items-center justify-center text-[#A3AED0] hover:bg-[#E2E8F0] hover:border-[#4318FF] transition-all cursor-pointer overflow-hidden group">
                <input type="file" @change="handleLogoChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept="image/*">
                <img v-if="logoPreview" :src="logoPreview" class="absolute inset-0 w-full h-full object-contain p-2 z-0" />
                <i v-if="!logoPreview" class="fas fa-image text-2xl mb-1 group-hover:text-[#4318FF] transition-colors"></i>
                <span class="text-[9px] font-black uppercase tracking-widest text-center px-4 relative z-10 transition-all" :class="logoPreview ? 'opacity-0 group-hover:opacity-100 bg-white/80 p-2 rounded text-[#4318FF]' : ''">
                  {{ projectForm.logo ? projectForm.logo.name : 'UPLOAD IMAGE' }}
                </span>
              </div>
            </div>
          </div>

        </div>

        <div class="px-8 pb-8 pt-4 shrink-0">
          <button @click="handleSaveProject" class="w-full bg-[#4318FF] text-white py-4 rounded-2xl text-[11px] font-black uppercase tracking-[0.15em] shadow-lg shadow-indigo-200 hover:bg-[#3311DB] active:scale-[0.98] transition-all">
            {{ isEditing ? 'SAVE CHANGES' : 'CONFIRM & CREATE PROJECT' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
.animate-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* Fix Select icon default untuk tema baru */
select {
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='%2394a3b8' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
  background-repeat: no-repeat;
  background-position-x: 95%;
  background-position-y: 50%;
}
</style>
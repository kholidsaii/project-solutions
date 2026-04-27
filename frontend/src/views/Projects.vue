<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import api from '../api/axios'; 
import WorksChart from '../components/Works.vue';
import { useRouter } from 'vue-router';

// ==========================================
// 1. GLOBAL STATE & NAVIGATION
// ==========================================
const router = useRouter();
const currentTab = ref('overview');
const isLoading = ref(true);

// Global Data Project
const workData = ref<any[]>([]);

// ==========================================
// 2. TAB: OVERVIEW (Analytics & Stats)
// ==========================================
const stats = ref([
  { label: 'Total', value: '0', color: 'bg-[#6366F1]', key: 'total' },
  { label: 'Finish', value: '0', color: 'bg-[#22D3EE]', key: 'finish' },
  { label: 'Progress', value: '0', color: 'bg-[#10B981]', key: 'progress' },
  { label: 'Planing', value: '0', color: 'bg-[#FBBF24]', key: 'planing' },
  { label: 'Pending', value: '0', color: 'bg-[#EF4444]', key: 'pending' },
]);

const barSeries = ref([{ name: 'Works Status', data: [] as number[] }]);

// Computed: Distribusi Category (Fix Error)
const donutSeriesCategory = computed(() => {
  const counts: Record<string, number> = {};
  workData.value.forEach(p => {
    const cat = p.category || 'Other';
    counts[cat] = (counts[cat] || 0) + 1;
  });
  return Object.values(counts).length ? Object.values(counts) : [0];
});

// Computed: Distribusi Package
const donutSeriesPackage = computed(() => {
  const counts: Record<string, number> = {};
  workData.value.forEach(p => {
    if (p.package && p.package !== '-') {
      counts[p.package] = (counts[p.package] || 0) + 1;
    }
  });
  return Object.values(counts).length ? Object.values(counts) : [0];
});

// Computed: Distribusi Status
const donutSeriesStatus = computed(() => {
  const counts: Record<string, number> = {};
  workData.value.forEach(p => {
    counts[p.status] = (counts[p.status] || 0) + 1;
  });
  return Object.values(counts).length ? Object.values(counts) : [0];
});

// Computed: Distribusi Priority
const priorityData = computed(() => {
  const priorities = ['Urgent', 'High', 'Medium', 'Low', 'Planned'];
  const counts = priorities.map(label => {
    return workData.value.filter(p => p.priority === label).length;
  });
  return counts.some(c => c > 0) ? counts : [0, 0, 0, 0, 0];
});

// Helper: Chart Legend & Data
const getSeriesDataByTitle = (title: string) => {
  if (title === 'Category') return donutSeriesCategory.value;
  if (title === 'Package') return donutSeriesPackage.value;
  if (title === 'Status') return donutSeriesStatus.value;
  return [0];
};

const getLabelsByTitle = (title: string) => {
  const counts: Record<string, number> = {};
  workData.value.forEach(p => {
    const val = title === 'Category' ? p.category : 
                title === 'Package' ? p.package : p.status;
    if (val && val !== '-') counts[val] = (counts[val] || 0) + 1;
  });
  return Object.keys(counts);
};

// ==========================================
// 3. TAB: DATA (Filtering & Detail)
// ==========================================
const activeFilter = ref({ type: 'all', value: 'all' });
const openMenu = ref<string | null>('status');

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
    if (type === 'category') return project.category === value;
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
const getImageUrl = (path: string | null): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith('data:') || path.startsWith('http')) return path;

  // Kita ambil 'http://localhost:8000/api' dari .env kamu
  const apiUrl = import.meta.env.VITE_API_URL; 
  
  // Kita buang '/api' di ujungnya biar jadi 'http://localhost:8000'
  const baseUrl = apiUrl.replace('/api', '');

  // Bersihkan path dari double slash atau prefix uploads
  let cleanPath = path.startsWith('/') ? path.substring(1) : path;
  console.log("Cleaned Image Path:", cleanPath); // Debug: Lihat path yang sudah dibersihkan
  // Gabungkan: http://localhost:8000 + / + uploads/categories/xxx.jpg
  return `${baseUrl}/${cleanPath}`;
};
// ==========================================
// 4. TAB: SETUP (Master Data Management)
// ==========================================
const setupTab = ref('categories');
const isEditing = ref(false);
const editingId = ref<number | null>(null);
const selectedFile = ref<File | null>(null);
const setupForm = ref({ name: '', icon: 'fas fa-folder' });

const allMasterData = ref({
  categories: [] as any[],
  status: [] as any[],
  priority: [] as any[],
  package: [] as any[]
});

const filteredMasterData = computed(() => {
  const key = setupTab.value as keyof typeof allMasterData.value;
  return allMasterData.value[key] || [];
});

const onFileChange = (e: any) => {
  selectedFile.value = e.target.files[0];
};

const handleEditMaster = (item: any) => {
  isEditing.value = true;
  editingId.value = item.id;
  setupForm.value.name = item.name;
  setupForm.value.icon = item.icon || 'fas fa-folder';
};

const cancelEdit = () => {
  isEditing.value = false;
  editingId.value = null;
  setupForm.value = { name: '', icon: 'fas fa-folder' };
  selectedFile.value = null;
};

// ==========================================
// 5. API ACTIONS (Fetching & Saving)
// ==========================================
const fetchStats = async () => {
  try {
    const res = await api.get('/works/stats');
    if (res.data) {
      stats.value.forEach((s: any) => {
        if (res.data.summary && res.data.summary[s.key] !== undefined) {
          s.value = res.data.summary[s.key].toLocaleString();
        }
      });
      barSeries.value = [{ name: 'Works Status', data: res.data.monthly || [] }];
    }
  } catch (e) {
    console.error("Gagal ambil statistik", e);
  }
};

const fetchProjects = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/projects');
    workData.value = res.data;
  } catch (e) {
    console.error("Gagal ambil data project", e);
  } finally {
    isLoading.value = false;
  }
};

const fetchMasterData = async () => {
  try {
    // Sync Categories
    const resCat = await api.get('/work-categories');
    allMasterData.value.categories = resCat.data;
    
    // Sync Master All (Jika endpoint /master-data/all sudah ada)
    // const resAll = await api.get('/master-data/all');
    // allMasterData.value = resAll.data;
  } catch (e) {
    console.error("Gagal load master data", e);
  }
};

const handleSaveMaster = async () => {
  if (!setupForm.value.name) return alert("Nama wajib diisi!");

  const formData = new FormData();
  formData.append('name', setupForm.value.name);
  
  if (setupTab.value === 'categories') {
    formData.append('icon', setupForm.value.icon || 'fas fa-folder');
    if (selectedFile.value) {
      formData.append('image', selectedFile.value);
    }
  }

  try {
    let url = `/master-data/${setupTab.value}`;
    
    if (isEditing.value && editingId.value) {
      // UNTUK UPDATE:
      url = `/master-data/${setupTab.value}/${editingId.value}`;
      
      // Trik Laravel: Gunakan POST tapi selipkan _method PUT
      // Karena multipart/form-data sering gagal kalau pakai method PUT asli
      formData.append('_method', 'PUT');
      
      await api.post(url, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert("Data berhasil diupdate!");
    } else {
      // UNTUK TAMBAH BARU:
      await api.post(url, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      alert("Data berhasil disimpan!");
    }

    cancelEdit();
    fetchMasterData(); // Refresh list
  } catch (e: any) {
    console.error(e);
    alert("Gagal memproses data: " + (e.response?.data?.message || e.message));
  }
};

const handleDeleteMaster = async (id: number) => {
  if (confirm('Yakin ingin menghapus data ini?')) {
    try {
      await api.delete(`/master-data/${setupTab.value}/${id}`);
      alert("Data berhasil dihapus!");
      fetchMasterData();
    } catch (e) {
      alert("Gagal menghapus data.");
    }
  }
};

// ==========================================
// 6. LIFECYCLE & WATCHERS
// ==========================================
onMounted(() => {
  fetchStats();
  fetchProjects();
  fetchMasterData();
});

watch(currentTab, (newTab) => {
  if (newTab === 'data') fetchProjects();
  if (newTab === 'overview') fetchStats();
  if (newTab === 'setup') fetchMasterData();
});
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans overflow-x-hidden text-slate-900">
    
    <div class="max-w-6xl mx-auto px-6 mt-8">
      
      <div class="bg-white border-x border-t border-slate-200 rounded-t-3xl pt-8 pb-4 relative shadow-sm">
        <div class="flex items-center gap-6 px-8">
          <div class="relative z-10">
            <div class="w-16 h-16 bg-[#EF4444] rounded-2xl flex items-center justify-center shadow-[0_12px_30px_-5px_rgba(239,68,68,0.35)] absolute -bottom-10 left-0">
              <i class="fas fa-check text-3xl text-white"></i>
            </div>
            <div class="w-16 h-6"></div> 
          </div>
          <div>
            <h1 class="text-[26px] font-black text-[#2E3A8C] tracking-tighter leading-none mb-2 uppercase">Works</h1>
          </div>
        </div>
      </div>

      <div class="bg-[#F1F5F9] border-x border-y border-slate-200 py-3 px-8 md:pl-[120px]"> 
        <div class="flex gap-6">
          <button v-for="tab in ['overview', 'data', 'setup']" :key="tab"
            @click="currentTab = tab" 
            class="group text-[11px] font-bold flex items-center gap-2 transition-all capitalize"
            :class="currentTab === tab ? 'text-[#2E3A8C]' : 'text-slate-400'">
            <div class="w-4 h-4 rounded bg-slate-200 flex items-center justify-center text-[8px] transition-colors" 
                 :class="currentTab === tab ? 'bg-[#2E3A8C] text-white' : 'group-hover:bg-slate-300'">
              <i class="fas fa-check"></i>
            </div>
            {{ tab }}
          </button>
        </div>
      </div>

   <div v-if="currentTab === 'overview'" class="animate-in fade-in duration-700 space-y-10 pb-20">
      
      <div class="bg-white border border-slate-100 rounded-[2.5rem] shadow-[0_20px_50px_-20px_rgba(0,0,0,0.05)] overflow-hidden">
        <div class="flex justify-between items-center px-10 py-6 border-b border-slate-50 bg-slate-50/30">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center">
              <i class="fas fa-chart-line text-indigo-600"></i>
            </div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Overview Analysis</h2>
          </div>
          <button class="w-12 h-12 rounded-full bg-white shadow-sm border border-slate-100 text-slate-400 hover:text-indigo-600 transition-all">
            <i class="fas fa-calendar-alt text-lg"></i>
          </button>
        </div>

        <div class="p-10">
          <div class="grid grid-cols-2 md:grid-cols-5 gap-6 mb-16">
            <div v-for="s in stats" :key="s.label" 
              class="group relative p-6 rounded-3xl transition-all duration-500 hover:-translate-y-2"
              :class="s.color">
              <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-3xl"></div>
              <p class="text-2xl font-black text-white leading-none mb-2">{{ s.value }}</p>
              <p class="text-[10px] font-bold text-white/80 uppercase tracking-widest">{{ s.label }}</p>
            </div>
          </div>

          <div class="relative px-4">
            <WorksChart :series-data="barSeries" chart-type="bar" :height="400" title="Monthly Performance" />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <WorksChart :series-data="donutSeriesCategory" chart-type="donut" title="Works Category" />
        <WorksChart :series-data="donutSeriesPackage" chart-type="donut" title="Works Package" />
        <WorksChart :series-data="donutSeriesStatus" chart-type="donut" title="Works Status" />
      </div>

      <div class="bg-white border border-slate-100 rounded-[2.5rem] shadow-sm p-10">
        <div class="flex items-center gap-4 mb-10">
          <div class="w-2 h-8 bg-rose-500 rounded-full"></div>
          <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Priority Distribution</h3>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          <div class="lg:col-span-4">
            <WorksChart :series-data="priorityData" chart-type="donut" :height="320" />
          </div>
          <div class="lg:col-span-8">
            <WorksChart :series-data="[{ name: 'Priority', data: priorityData }]" chart-type="bar-priority" :height="350" />
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div v-for="title in ['Category', 'Package', 'Status']" :key="title" 
          class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm hover:shadow-md transition-all">
          <h4 class="text-center text-slate-400 font-bold text-[10px] mb-6 uppercase tracking-[0.2em]">Works {{ title }}</h4>
          <div class="flex items-center gap-6">
            <div class="w-1/2">
              <WorksChart :series-data="getSeriesDataByTitle(title)" chart-type="donut-mini" :height="180" />
            </div>
            <div class="w-1/2 space-y-3">
              <div v-for="(name, idx) in getLabelsByTitle(title).slice(0, 4)" :key="idx" class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-lg bg-slate-50 flex items-center justify-center text-[9px] font-black text-indigo-600">{{ idx + 1 }}</span>
                <span class="text-[10px] font-bold text-slate-600 truncate uppercase">{{ name }}</span>
              </div>
              <p v-if="getLabelsByTitle(title).length > 4" class="text-[9px] text-slate-300 font-bold ml-7">+ More Items</p>
            </div>
          </div>
        </div>
      </div>
    </div>

     <div v-if="currentTab === 'data'" class="flex flex-col md:flex-row gap-4 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-20">
    
       <aside class="w-full md:w-64 flex-none space-y-4">
          <div class="bg-white border border-slate-200 rounded-[2rem] overflow-hidden shadow-sm">
            <div class="bg-slate-50/50 border-b border-slate-100 px-6 py-4">
              <span class="text-[11px] font-black text-blue-900 uppercase tracking-[0.2em]">Navigation</span>
            </div>
            
            <div class="p-3 space-y-2">
              <button @click="setFilter('all', 'all'); openMenu = null"
                class="w-full flex items-center gap-3 px-4 py-3 text-[11px] font-black uppercase rounded-2xl transition-all shadow-sm"
                :class="activeFilter.type === 'all' ? 'bg-blue-700 text-white shadow-blue-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                <i class="fas fa-layer-group"></i> All Projects
              </button>

              <div class="space-y-1">
                <button @click="toggleMenu('status')"
                  class="w-full flex items-center justify-between px-4 py-3 text-[11px] font-black uppercase text-slate-500 hover:bg-slate-50 rounded-2xl transition-all">
                  <div class="flex items-center gap-3">
                    <i class="fas fa-folder-open" :class="openMenu === 'status' ? 'text-blue-600' : 'text-slate-400'"></i>
                    <span>All Status</span>
                  </div>
                  <i class="fas fa-chevron-down transition-transform duration-300 text-[10px]" :class="openMenu === 'status' ? 'rotate-180' : ''"></i>
                </button>
                
                <div v-show="openMenu === 'status'" class="ml-6 border-l-2 border-slate-100 pl-4 space-y-1 animate-in slide-in-from-top-2 duration-300">
                  <button v-for="s in ['Total', 'Finish', 'Progress', 'Planning', 'Pending']" :key="s"
                    @click="setFilter('status', s)"
                    class="w-full text-left px-3 py-2 text-[10px] font-bold rounded-xl transition-all"
                    :class="activeFilter.value === s ? 'text-blue-700 bg-blue-50' : 'text-slate-400 hover:text-blue-600'">
                    — {{ s }}
                  </button>
                </div>
              </div>

              <div class="space-y-1">
                <button @click="toggleMenu('priority')"
                  class="w-full flex items-center justify-between px-4 py-3 text-[11px] font-black uppercase text-slate-500 hover:bg-slate-50 rounded-2xl transition-all">
                  <div class="flex items-center gap-3">
                    <i class="fas fa-folder-open" :class="openMenu === 'priority' ? 'text-blue-600' : 'text-slate-400'"></i>
                    <span>All Priority</span>
                  </div>
                  <i class="fas fa-chevron-down transition-transform duration-300 text-[10px]" :class="openMenu === 'priority' ? 'rotate-180' : ''"></i>
                </button>
                
                <div v-show="openMenu === 'priority'" class="ml-6 border-l-2 border-slate-100 pl-4 space-y-1 animate-in slide-in-from-top-2 duration-300">
                  <button v-for="p in ['Urgent', 'High', 'Medium', 'Low', 'Planned']" :key="p"
                    @click="setFilter('priority', p)"
                    class="w-full text-left px-3 py-2 text-[10px] font-bold rounded-xl transition-all"
                    :class="activeFilter.value === p ? 'text-blue-700 bg-blue-50' : 'text-slate-400 hover:text-blue-600'">
                    — {{ p }}
                  </button>
                </div>
              </div>
              <div class="space-y-1">
                      <button @click="toggleMenu('category')"
                        class="w-full flex items-center justify-between px-4 py-3 text-[11px] font-black uppercase text-slate-500 hover:bg-slate-50 rounded-2xl transition-all">
                        <div class="flex items-center gap-3">
                          <i class="fas fa-folder-open" :class="openMenu === 'category' ? 'text-blue-600' : 'text-slate-400'"></i>
                          <span>All Category</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-[10px]" :class="openMenu === 'category' ? 'rotate-180' : ''"></i>
                      </button>
                      
                      <div v-show="openMenu === 'category'" class="ml-6 border-l-2 border-slate-100 pl-4 space-y-1 animate-in slide-in-from-top-2 duration-300">
                          <button v-for="cat in allMasterData.categories" :key="cat.id"
                            @click="setFilter('category', cat.name)"
                            class="w-full text-left px-3 py-2 text-[10px] font-bold rounded-xl transition-all truncate"
                            :class="activeFilter.value === cat.name ? 'text-blue-700 bg-blue-50' : 'text-slate-400 hover:text-blue-600'">
                            — {{ cat.name }}
                          </button>

                          <p v-if="allMasterData.categories.length === 0" class="text-[9px] text-slate-300 italic pl-3">
                            No categories found
                          </p>
                        </div>
                      </div>

                    <div class="space-y-1">
                      <button @click="toggleMenu('package')"
                        class="w-full flex items-center justify-between px-4 py-3 text-[11px] font-black uppercase text-slate-500 hover:bg-slate-50 rounded-2xl transition-all">
                        <div class="flex items-center gap-3">
                          <i class="fas fa-folder-open" :class="openMenu === 'package' ? 'text-blue-600' : 'text-slate-400'"></i>
                          <span>All Package</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-300 text-[10px]" :class="openMenu === 'package' ? 'rotate-180' : ''"></i>
                      </button>
                      
                      <div v-show="openMenu === 'package'" class="ml-6 border-l-2 border-slate-100 pl-4 space-y-1 animate-in slide-in-from-top-2 duration-300">
                        <button v-for="pkg in allMasterData.package" :key="pkg.id"
                          @click="setFilter('package', pkg.name)"
                          class="w-full text-left px-3 py-2 text-[10px] font-bold rounded-xl transition-all truncate"
                          :class="activeFilter.value === pkg.name ? 'text-blue-700 bg-blue-50' : 'text-slate-400 hover:text-blue-600'">
                          — {{ pkg.name }}
                        </button>
                        <p v-if="allMasterData.package.length === 0" class="text-[9px] text-slate-300 italic pl-3">No packages</p>
                      </div>
                    </div>
              </div>
          </div>
        </aside>

        <main class="flex-1 space-y-3">
          <div v-if="activeFilter.type !== 'all'" class="flex items-center gap-2 mb-2">
            <span class="text-[9px] font-bold bg-blue-100 text-blue-700 px-3 py-1 rounded-full uppercase">
              Filtering by {{ activeFilter.type }}: {{ activeFilter.value }}
            </span>
            <button @click="setFilter('all', 'all')" class="text-[9px] font-bold text-rose-500 hover:underline">Clear</button>
          </div>

          <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 flex items-center gap-2.5 shadow-sm">
            <i class="fas fa-th-large text-[#2E3A8C] text-xs"></i>
            <span class="text-[11px] font-black text-[#2E3A8C] uppercase tracking-wider">Works Detail</span>
          </div>

          <div v-for="project in filteredWorkData" :key="project.id"
             @click="viewProjectDetail(project.id)" class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm flex flex-col md:flex-row relative mb-3 transition-all hover:shadow-md">
            
            <div class="w-1 h-full absolute left-0 top-0" :class="project.color || 'bg-green-500'"></div>

            <div class="p-3 flex flex-col items-center justify-center border-r border-slate-50 md:w-26 flex-none">
              <div class="w-14 h-14 bg-white flex items-center justify-center p-1 mb-1 text-slate-200 overflow-hidden rounded-lg border border-slate-100">
                <img v-if="project.logo" 
                    :src="getImageUrl(project.logo)" 
                    class="max-w-full max-h-full object-contain" />
                
                <i v-else class="fas fa-image text-2xl opacity-10"></i>
              </div>
              <span class="text-[8px] font-medium text-slate-400 tracking-tighter uppercase">ID-{{ project.id }}</span>
            </div>

            <div class="flex-1 p-3 overflow-hidden">
              <div class="mb-2 border-b border-slate-50 pb-1.5">
                <p class="text-[7px] text-slate-300 italic mb-0.5">Crate by : Suhery Solutions 10/11/2024</p>
                <h3 class="text-[11px] font-black text-blue-700 uppercase leading-none tracking-tight">
                  {{ project.project_title }}
                </h3>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-4 gap-y-0.5">
                
                <div class="space-y-0.5">
                  <div class="flex text-[9px] leading-tight items-start">
                    <span class="w-16 text-slate-500 font-medium uppercase flex-none">Customer</span>
                    <span class="font-bold text-slate-800 break-words flex-1">: {{ project.client_name }}</span>
                  </div>
                  <div class="flex text-[9px] leading-tight items-center">
                    <span class="w-16 text-slate-500 font-medium uppercase flex-none">Start Date</span>
                    <span class="font-bold text-slate-800">: {{ project.start_date }}</span>
                  </div>
                  <div class="flex text-[9px] leading-tight items-center">
                    <span class="w-16 text-slate-500 font-medium uppercase flex-none">Finish Data</span>
                    <span class="font-bold text-slate-800">: {{ project.finish_date }}</span>
                  </div>
                  <div class="flex text-[9px] leading-tight items-center">
                    <span class="w-16 text-slate-500 font-medium uppercase flex-none">Total Day</span>
                    <span class="font-bold text-slate-800">: {{ project.total_day }}</span>
                  </div>
                </div>

                <div class="space-y-0.5 border-l border-slate-50 lg:pl-3">
                  <div class="flex text-[9px] leading-tight items-center">
                    <span class="w-24 text-slate-500 font-medium uppercase flex-none">Work Category</span>
                    <span class="font-bold text-slate-800">: {{ project.category }}</span>
                  </div>
                  <div class="flex text-[9px] leading-tight items-center">
                    <span class="w-24 text-slate-500 font-medium uppercase flex-none">Works Status</span>
                    <span class="font-bold text-slate-800">: {{ project.status }}</span>
                  </div>
                  <div class="flex text-[9px] leading-tight items-center">
                    <span class="w-24 text-slate-500 font-medium uppercase flex-none">Works Priority</span>
                    <span class="font-bold text-slate-800">: {{ project.priority }}</span>
                  </div>
                  <div class="flex text-[9px] leading-tight items-center">
                    <span class="w-24 text-slate-500 font-medium uppercase flex-none">Works Package</span>
                    <span class="font-bold text-slate-800">: {{ project.package }}</span>
                  </div>
                </div>

              </div>
            </div>

            <div class="md:w-22 bg-slate-50/30 flex flex-col divide-y divide-slate-100 border-l border-slate-100">
              <div class="flex-1 flex flex-col items-center justify-center p-2 text-center">
                <p class="text-base font-black text-blue-700 leading-none mb-0.5">{{ project.progress }} %</p>
                <p class="text-[7px] font-bold text-blue-900 uppercase tracking-widest">Progress</p>
              </div>
              <div class="flex-1 flex flex-col items-center justify-center p-2 text-center">
                <p class="text-base font-black text-blue-700 leading-none">{{ project.team_count }}</p>
                <p class="text-[7px] font-bold text-blue-900 uppercase tracking-tighter leading-none mt-0.5">Total Teamwork</p>
              </div>
            </div>

          </div>
        </main>
      </div>
      
    

    <div v-if="currentTab === 'setup'" class="flex flex-col md:flex-row gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-20">
  
      <aside class="w-full md:w-64 flex-none">
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
          <div class="bg-slate-50 border-b border-slate-200 px-4 py-3">
            <span class="text-[10px] font-black text-[#2E3A8C] uppercase tracking-widest">Master Data</span>
          </div>
          <div class="p-2 space-y-1">
            <button v-for="menu in [
              { id: 'categories', label: 'Work Categories', icon: 'fas fa-tags' },
              { id: 'status', label: 'Works Status', icon: 'fas fa-tasks' },
              { id: 'priority', label: 'Works Priority', icon: 'fas fa-exclamation-circle' },
              { id: 'package', label: 'Works Package', icon: 'fas fa-box-open' }
            ]" :key="menu.id"
              @click="setupTab = menu.id"
              class="w-full flex items-center gap-3 px-4 py-2.5 text-[10px] font-black uppercase rounded-lg transition-all"
              :class="setupTab === menu.id ? 'bg-[#2E3A8C] text-white shadow-md' : 'text-slate-400 hover:bg-slate-50'">
              <i :class="menu.icon" class="w-4"></i>
              {{ menu.label }}
            </button>
          </div>
        </div>
      </aside>

      <main class="flex-1 space-y-6">
        
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm transition-all" :class="isEditing ? 'ring-2 ring-amber-100 bg-amber-50/10' : ''">
            <h3 class="text-xs font-black text-[#2E3A8C] uppercase mb-6 tracking-widest flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i :class="isEditing ? 'fas fa-edit text-amber-500' : 'fas fa-plus-circle text-blue-600'"></i>
                {{ isEditing ? 'Edit' : 'Add New' }} {{ setupTab }}
              </div>
              <button v-if="isEditing" @click="cancelEdit" class="text-[9px] font-bold text-rose-500 hover:underline">Cancel Edit</button>
            </h3>
            
           <div class="flex flex-col lg:flex-row flex-wrap gap-4 items-end">
  
              <div class="w-full lg:flex-1 min-w-[150px]">
                <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Name / Title</label>
                <input v-model="setupForm.name" type="text" 
                  class="w-full mt-1 bg-slate-50 border-none rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-blue-100 transition-all"
                  placeholder="Enter name...">
              </div>

              <div v-if="setupTab === 'categories'" class="w-full lg:w-72 flex-none">
                <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Icon / Logo</label>
                <div class="flex gap-2 items-center mt-1">
                  <input v-model="setupForm.icon" type="text" 
                    class="flex-1 bg-slate-50 border-none rounded-xl px-3 py-3 text-[10px] font-bold outline-none"
                    placeholder="fas fa-tag">
                  
                  <label class="w-11 h-11 bg-white border-2 border-dashed border-slate-200 rounded-xl flex-none flex items-center justify-center cursor-pointer hover:border-blue-400 transition-all shadow-sm">
                    <input type="file" @change="onFileChange" class="hidden" accept="image/*">
                    <i class="fas fa-image text-slate-400"></i>
                  </label>
                </div>
              </div>

              <div class="w-full lg:w-auto flex-none">
                <button @click="handleSaveMaster" 
                  class="w-full lg:w-48 text-white px-8 py-3.5 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg transition-all active:scale-95"
                  :class="isEditing ? 'bg-amber-500 hover:bg-amber-600 shadow-amber-100' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-100'">
                  {{ isEditing ? 'Update Data' : 'Save Data' }}
                </button>
              </div>

            </div>
          </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-100">
                <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest w-16">No</th>
                <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Name</th>
                <th v-if="setupTab === 'categories'" class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest w-24 text-center">Icon</th>
                <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="(item, index) in filteredMasterData" :key="item.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 text-[10px] font-bold text-slate-300">#{{ index + 1 }}</td>
                <td class="px-6 py-4">
                  <span class="text-[11px] font-bold text-[#2E3A8C] uppercase tracking-tight">{{ item.name }}</span>
                </td>
                <td v-if="setupTab === 'categories'" class="px-6 py-4 text-center">
                  <i :class="item.icon || 'fas fa-folder'" class="text-slate-400"></i>
                </td>
                <td class="px-6 py-4 text-right flex items-center justify-end gap-1">
                  <button @click="handleEditMaster(item)" class="text-amber-400 hover:text-amber-600 transition-colors p-2">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="handleDeleteMaster(item.id)" class="text-rose-400 hover:text-rose-600 transition-colors p-2">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="filteredMasterData.length === 0">
                <td colspan="4" class="px-6 py-10 text-center text-slate-300 italic text-[10px]">
                  No data found for {{ setupTab }}.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </main>
    </div>

    </div> </div>
</template>

<style scoped>
.font-sans { font-family: 'Inter', sans-serif; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios';

const router = useRouter();

// --- STATE ---
const isLoading = ref(true);
const workData = ref<any[]>([]);
const activeFilter = ref({ type: 'all', value: 'all' });
const openMenu = ref<string | null>('status');

const allMasterData = ref({
  categories: [] as any[],
  package: [] as any[],
});

// --- METHODS & LOGIC ---
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

  const apiUrl = import.meta.env.VITE_API_URL || ''; 
  const baseUrl = apiUrl.replace('/api', '');
  let cleanPath = path.startsWith('/') ? path.substring(1) : path;
  
  if (cleanPath.toLowerCase().startsWith('uploads/')) {
    return `${baseUrl}/${cleanPath}`;
  } else {
    return `${baseUrl}/uploads/${cleanPath}`;
  }
};

// --- API ACTIONS ---
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

const fetchMasterDataForFilters = async () => {
  try {
    const [resCat, resPackage] = await Promise.all([
      api.get('/work-categories'),
      api.get('/master-data/package'),
    ]);
    allMasterData.value.categories = resCat.data;
    allMasterData.value.package = resPackage.data;
  } catch (e) {
    console.error("Gagal ambil master data untuk filter", e);
  }
};

onMounted(() => {
  fetchProjects();
  fetchMasterDataForFilters();
});
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 pb-20 mt-4">
    
    <!-- SIDEBAR NAVIGATION (Kiri) -->
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 sticky top-8">
        
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Main Filters</h3>
        
        <div class="space-y-2">
          <!-- All Projects -->
          <button @click="setFilter('all', 'all'); openMenu = null"
            class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all text-left group"
            :class="activeFilter.type === 'all' ? 'bg-indigo-50 border border-indigo-100' : 'hover:bg-slate-50 border border-transparent'">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors"
              :class="activeFilter.type === 'all' ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-slate-600'">
              <i class="fas fa-layer-group"></i>
            </div>
            <span class="text-xs font-black uppercase tracking-tight" :class="activeFilter.type === 'all' ? 'text-indigo-600' : 'text-slate-500'">
              All Projects
            </span>
          </button>

          <!-- Accordion: Status -->
          <div class="border border-transparent" :class="openMenu === 'status' ? 'bg-slate-50 rounded-2xl border-slate-100 p-2' : ''">
            <button @click="toggleMenu('status')"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all text-left group hover:bg-slate-50">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-indigo-500"
                  :class="activeFilter.type === 'status' ? 'bg-indigo-100 text-indigo-600' : ''">
                  <i class="fas fa-tasks"></i>
                </div>
                <span class="text-xs font-black uppercase tracking-tight text-slate-500 group-hover:text-indigo-600">Status</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'status' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openMenu === 'status'" class="mt-2 ml-10 space-y-1 animate-in slide-in-from-top-2 duration-300">
              <button v-for="s in ['Total', 'Finish', 'Progress', 'Planning', 'Pending']" :key="s"
                @click="setFilter('status', s)"
                class="w-full text-left px-3 py-2 text-[10px] font-bold rounded-xl transition-all"
                :class="activeFilter.value === s ? 'text-indigo-600 bg-indigo-50' : 'text-slate-400 hover:text-indigo-500 hover:bg-slate-100'">
                — {{ s }}
              </button>
            </div>
          </div>

          <!-- Accordion: Priority -->
          <div class="border border-transparent" :class="openMenu === 'priority' ? 'bg-slate-50 rounded-2xl border-slate-100 p-2' : ''">
            <button @click="toggleMenu('priority')"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all text-left group hover:bg-slate-50">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-rose-500"
                  :class="activeFilter.type === 'priority' ? 'bg-rose-100 text-rose-600' : ''">
                  <i class="fas fa-fire-alt"></i>
                </div>
                <span class="text-xs font-black uppercase tracking-tight text-slate-500 group-hover:text-rose-600">Priority</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'priority' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openMenu === 'priority'" class="mt-2 ml-10 space-y-1 animate-in slide-in-from-top-2 duration-300">
              <button v-for="p in ['Urgent', 'High', 'Medium', 'Low', 'Planned']" :key="p"
                @click="setFilter('priority', p)"
                class="w-full text-left px-3 py-2 text-[10px] font-bold rounded-xl transition-all"
                :class="activeFilter.value === p ? 'text-rose-600 bg-rose-50' : 'text-slate-400 hover:text-rose-500 hover:bg-slate-100'">
                — {{ p }}
              </button>
            </div>
          </div>

          <!-- Accordion: Package -->
          <div class="border border-transparent" :class="openMenu === 'package' ? 'bg-slate-50 rounded-2xl border-slate-100 p-2' : ''">
            <button @click="toggleMenu('package')"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all text-left group hover:bg-slate-50">
              <div class="flex items-center gap-4">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors bg-slate-100 text-slate-400 group-hover:bg-slate-200 group-hover:text-amber-500"
                  :class="activeFilter.type === 'package' ? 'bg-amber-100 text-amber-600' : ''">
                  <i class="fas fa-box"></i>
                </div>
                <span class="text-xs font-black uppercase tracking-tight text-slate-500 group-hover:text-amber-600">Package</span>
              </div>
              <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform duration-300" :class="openMenu === 'package' ? 'rotate-180' : ''"></i>
            </button>
            <div v-show="openMenu === 'package'" class="mt-2 ml-10 space-y-1 animate-in slide-in-from-top-2 duration-300">
              <button v-for="pkg in allMasterData.package" :key="pkg.id"
                @click="setFilter('package', pkg.name)"
                class="w-full text-left px-3 py-2 text-[10px] font-bold rounded-xl transition-all truncate"
                :class="activeFilter.value === pkg.name ? 'text-amber-600 bg-amber-50' : 'text-slate-400 hover:text-amber-500 hover:bg-slate-100'">
                — {{ pkg.name }}
              </button>
              <p v-if="allMasterData.package.length === 0" class="text-[9px] text-slate-300 italic pl-3">No packages</p>
            </div>
          </div>
        </div>

        <!-- LABELS (Kategori diubah jadi Tags seperti di Financial) -->
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-8 mb-4 ml-2">Categories / Tags</h3>
        <div class="flex flex-wrap gap-2 px-2">
          <button v-for="cat in allMasterData.categories" :key="cat.id" 
            @click="setFilter('category', cat.name)"
            class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border transition-all"
            :class="activeFilter.value === cat.name ? 'bg-indigo-600 text-white border-indigo-600 shadow-md ring-2 ring-offset-2 ring-indigo-200' : 'bg-white text-slate-500 border-slate-200 hover:border-indigo-300 hover:text-indigo-600'">
            # {{ cat.name }}
          </button>
          <p v-if="allMasterData.categories.length === 0" class="text-[9px] text-slate-300 italic w-full text-center py-2">
            No categories found
          </p>
        </div>

      </div>
    </div>

    <!-- MAIN CONTENT (Kanan) -->
    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[600px] relative">
      
      <!-- Loading Overlay -->
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center rounded-[3rem]">
        <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
      </div>

      <!-- Top Bar: Filter Status & Action Button -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4 relative z-10">
        <div class="flex items-center gap-4 w-full md:w-auto">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Current Filter:</span>
          <div class="bg-slate-50 border border-slate-100 text-indigo-700 text-[10px] font-bold uppercase py-3 px-5 rounded-xl flex items-center gap-3 min-w-[200px]">
            <span v-if="activeFilter.type === 'all'">All Projects</span>
            <span v-else>{{ activeFilter.type }}: {{ activeFilter.value }}</span>
            <button v-if="activeFilter.type !== 'all'" @click="setFilter('all', 'all')" class="ml-auto text-rose-400 hover:text-rose-600 transition-colors">
              <i class="fas fa-times-circle text-sm"></i>
            </button>
          </div>
        </div>

        <button class="w-full md:w-auto bg-indigo-600 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center justify-center gap-3">
          <i class="fas fa-plus"></i> Create Project
        </button>
      </div>

      <!-- Content Area (Membungkus List Card Project) -->
      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden flex flex-col p-6 lg:p-8">
        
        <!-- Header Daftar -->
        <div class="flex items-center gap-4 mb-6 px-2 border-b border-slate-50 pb-6">
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
            <i class="fas fa-th-large"></i>
          </div>
          <div>
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Works Detail Directory</h2>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Daftar Project & Laporan</p>
          </div>
        </div>

        <!-- List Project Cards -->
        <div class="space-y-4 overflow-y-auto custom-scroll pr-2 pb-4">
          <div v-for="project in filteredWorkData" :key="project.id"
              @click="viewProjectDetail(project.id)" 
              class="cursor-pointer bg-slate-50/40 border border-slate-100 rounded-2xl overflow-hidden flex flex-col md:flex-row relative transition-all duration-300 hover:bg-white hover:shadow-lg hover:-translate-y-1 group">
            
            <!-- Garis Warna Status -->
            <div class="w-1.5 h-full absolute left-0 top-0 transition-colors" :class="project.color || 'bg-slate-300'"></div>

            <!-- Logo & ID -->
            <div class="p-4 flex flex-col items-center justify-center border-r border-slate-100 md:w-28 flex-none bg-white/50">
              <div class="w-16 h-16 bg-white flex items-center justify-center p-2 mb-2 text-slate-200 rounded-xl border border-slate-100 shadow-sm group-hover:shadow transition-shadow">
                <img v-if="project.logo" :src="getImageUrl(project.logo)" class="max-w-full max-h-full object-contain" />
                <i v-else class="fas fa-image text-2xl opacity-20"></i>
              </div>
              <span class="text-[9px] font-black text-slate-400 tracking-widest uppercase">ID-{{ project.id }}</span>
            </div>

            <!-- Info Utama -->
            <div class="flex-1 p-5 overflow-hidden">
              <div class="mb-3 border-b border-slate-100 pb-2">
                <p class="text-[8px] text-slate-400 font-bold uppercase tracking-widest mb-1">Created by : System</p>
                <h3 class="text-sm font-black text-[#2E3A8C] uppercase tracking-tight group-hover:text-indigo-600 transition-colors">
                  {{ project.project_title }}
                </h3>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-6 gap-y-2">
                <div class="space-y-1.5">
                  <div class="flex text-[10px] items-center">
                    <span class="w-20 text-slate-400 font-bold uppercase tracking-wider flex-none">Customer</span>
                    <span class="font-black text-slate-700 truncate">: {{ project.client_name }}</span>
                  </div>
                  <div class="flex text-[10px] items-center">
                    <span class="w-20 text-slate-400 font-bold uppercase tracking-wider flex-none">Start Date</span>
                    <span class="font-black text-slate-700">: {{ project.start_date }}</span>
                  </div>
                  <div class="flex text-[10px] items-center">
                    <span class="w-20 text-slate-400 font-bold uppercase tracking-wider flex-none">Finish Date</span>
                    <span class="font-black text-slate-700">: {{ project.finish_date }}</span>
                  </div>
                </div>

                <div class="space-y-1.5 lg:border-l lg:border-slate-100 lg:pl-5">
                  <div class="flex text-[10px] items-center">
                    <span class="w-24 text-slate-400 font-bold uppercase tracking-wider flex-none">Category</span>
                    <span class="font-black text-slate-700">: {{ project.category }}</span>
                  </div>
                  <div class="flex text-[10px] items-center">
                    <span class="w-24 text-slate-400 font-bold uppercase tracking-wider flex-none">Status</span>
                    <span class="font-black px-2 py-0.5 rounded text-[8px] uppercase tracking-widest ml-1 border"
                      :class="project.status === 'Finish' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100'">
                      {{ project.status }}
                    </span>
                  </div>
                  <div class="flex text-[10px] items-center">
                    <span class="w-24 text-slate-400 font-bold uppercase tracking-wider flex-none">Priority</span>
                    <span class="font-black text-slate-700">: {{ project.priority }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Panel Kanan (Progress & Team) -->
            <div class="md:w-32 bg-slate-50 flex flex-col divide-y divide-slate-100 border-l border-slate-100">
              <div class="flex-1 flex flex-col items-center justify-center p-4 text-center group-hover:bg-indigo-50 transition-colors">
                <p class="text-xl font-black text-indigo-600 leading-none mb-1">{{ project.progress || 0 }}%</p>
                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Progress</p>
              </div>
              <div class="flex-1 flex flex-col items-center justify-center p-4 text-center group-hover:bg-blue-50 transition-colors">
                <p class="text-xl font-black text-blue-600 leading-none mb-1">{{ project.team_count || 0 }}</p>
                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Teamwork</p>
              </div>
            </div>
          </div>
          
          <!-- Empty State -->
          <div v-if="!isLoading && filteredWorkData.length === 0" class="text-center py-20 border-2 border-dashed border-slate-200 rounded-3xl opacity-60">
            <i class="fas fa-folder-open text-5xl text-slate-300 mb-4"></i>
            <p class="text-xs font-black text-slate-500 uppercase tracking-widest">No projects found</p>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 6px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>
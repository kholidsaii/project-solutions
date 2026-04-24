<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'; // Import sudah lengkap
import api from '../api/axios'; 
import WorksChart from '../components/Works.vue';

// --- 1. STATE MANAGEMENT ---
const currentTab = ref('overview');
const setupTab = ref('categories');
const isLoading = ref(true);

// Data Master & UI
const stats = ref([
  { label: 'Total', value: '0', color: 'bg-[#6366F1]', key: 'total' },
  { label: 'Finish', value: '0', color: 'bg-[#22D3EE]', key: 'finish' },
  { label: 'Progress', value: '0', color: 'bg-[#10B981]', key: 'progress' },
  { label: 'Planing', value: '0', color: 'bg-[#FBBF24]', key: 'planing' },
  { label: 'Pending', value: '0', color: 'bg-[#EF4444]', key: 'pending' },
]);

const workData = ref<any[]>([]);
const barSeries = ref([{ name: 'Works Status', data: [] as number[] }]);
const donutSeries = ref([0, 0, 0, 0]);

// Data Master untuk Setup
const allMasterData = ref({
  categories: [] as any[],
  status: [] as any[],
  priority: [] as any[],
  package: [] as any[]
});

// FORM STATES
const setupForm = ref({ name: '', icon: 'fas fa-folder' });

// --- 2. COMPUTED (Yang tadi bikin error) ---
const filteredMasterData = computed(() => {
  // Mengambil data sesuai tab setup yang aktif
  const key = setupTab.value as keyof typeof allMasterData.value;
  return allMasterData.value[key] || [];
});

// --- 3. API ACTIONS ---
// Tambahkan fungsi ini di script setup Projects.vue
const handleDeleteMaster = async (id: number) => {
  if (confirm('Yakin ingin menghapus data ini?')) {
    try {
      // Sesuaikan endpoint-nya dengan backend kamu
      await api.delete(`/master-data/${setupTab.value}/${id}`);
      alert("Data berhasil dihapus!");
      fetchMasterData(); // Refresh list setelah hapus
    } catch (e) {
      console.error(e);
      alert("Gagal menghapus data.");
    }
  }
};
const fetchMasterData = async () => {
  try {
    const res = await api.get('/master-data/all');
    allMasterData.value = res.data;
  } catch (e) {
    console.error("Gagal load master data", e);
  }
};

const fetchStats = async () => {
  try {
    const res = await api.get('/works/stats');
    if (res.data) {
      stats.value.forEach(s => {
        if (res.data.summary[s.key] !== undefined) {
          s.value = res.data.summary[s.key].toLocaleString();
        }
      });
      barSeries.value = [{ name: 'Works Status', data: res.data.monthly }];
      donutSeries.value = res.data.categories;
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

const handleSaveMaster = async () => {
  if (!setupForm.value.name) return alert("Nama harus diisi!");
  try {
    await api.post(`/master-data/${setupTab.value}`, {
      name: setupForm.value.name,
      icon: setupTab.value === 'categories' ? setupForm.value.icon : null
    });
    setupForm.value.name = '';
    fetchMasterData(); // Refresh list setelah simpan
    alert("Data master berhasil disimpan!");
  } catch (e) {
    alert("Gagal menyimpan data master");
  }
};

// --- 4. LIFECYCLE ---
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

      <div v-if="currentTab === 'overview'" class="animate-in fade-in duration-500 space-y-6">
        
        <div class="bg-white border-x border-b border-slate-200 rounded-b-3xl shadow-sm overflow-hidden">
          <div class="flex justify-between items-center px-6 py-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <i class="fas fa-th text-[#2E3A8C] text-lg"></i>
              <h2 class="text-lg font-black text-[#2E3A8C]">Overview Analysis</h2>
            </div>
            <button class="text-[#2E3A8C] opacity-60 hover:opacity-100 transition-opacity">
              <i class="fas fa-calendar-alt text-2xl"></i>
            </button>
          </div>

          <div class="p-8">
            <div class="flex items-center gap-3 mb-8 text-[#2E3A8C]">
              <i class="fas fa-th-large text-sm"></i>
              <h3 class="text-lg font-bold">Works Status</h3>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-12">
              <div v-for="s in stats" :key="s.label" :class="s.color" 
                class="py-5 px-2 rounded-xl text-white text-center shadow-[0_8px_15px_-3px_rgba(0,0,0,0.1)] transition-transform hover:scale-105">
                <p class="text-xl font-black leading-none mb-1.5">{{ s.value }}</p>
                <p class="text-[11px] font-medium uppercase opacity-90 tracking-wide">{{ s.label }}</p>
              </div>
            </div>

            <div class="relative px-10">
              <button class="absolute left-0 top-1/2 -translate-y-1/2 text-slate-200 text-5xl hover:text-slate-300">
                <i class="fas fa-chevron-left"></i>
              </button>
              <div class="w-full">
                <WorksChart :series-data="barSeries" chart-type="bar" :height="350" />
              </div>
              <button class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-200 text-5xl hover:text-slate-300">
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <WorksChart :series-data="donutSeries" chart-type="donut" title="Works Category" />
          <WorksChart :series-data="donutSeries" chart-type="donut" title="Works Package" />
          <WorksChart :series-data="donutSeries" chart-type="donut" title="Works Status" />
        </div>

        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm p-8">
          <div class="flex items-center gap-3 mb-8 text-[#2E3A8C]">
            <i class="fas fa-th-large text-sm"></i>
            <h3 class="text-lg font-bold">Works Priority</h3>
          </div>
          <div class="flex flex-col md:flex-row items-center gap-10">
            <div class="w-full md:w-1/3">
              <WorksChart :series-data="[25, 28, 29, 19]" chart-type="donut" :height="250" />
            </div>
            <div class="w-full md:w-2/3 relative">
              <WorksChart :series-data="[{ name: 'Priority', data: [800, 50, 130, 150, 210] }]" chart-type="bar-priority" :height="280" />
              <i class="fas fa-chevron-right absolute -right-4 top-1/2 -translate-y-1/2 text-slate-100 text-6xl"></i>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-10">
          <div v-for="title in ['Category', 'Package', 'Status']" :key="title" class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm">
            <h4 class="text-center text-[#2E3A8C] font-bold text-sm mb-4 italic">Works {{ title }}</h4>
            <div class="flex items-center gap-2">
              <div class="w-1/2">
                <WorksChart :series-data="[40, 30, 20, 10]" chart-type="donut-mini" :height="180" />
              </div>
              <div class="w-1/2 text-[11px] font-bold text-[#2E3A8C] space-y-1.5">
                <p>1. Menu Satu</p>
                <p>2. Menu Dua</p>
                <p>3. Menu Tiga</p>
                <p>4. Menu Empat</p>
              </div>
            </div>
          </div>
        </div>
      </div>

     <div v-if="currentTab === 'data'" class="flex flex-col md:flex-row gap-4 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-20">
    
        <aside class="w-full md:w-56 flex-none">
          <div class="bg-[#F8FAFC] border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="bg-white border-b border-slate-200 px-3 py-2 text-center uppercase tracking-widest text-[9px] font-bold text-blue-800">Navigation</div>
            <div class="p-1.5 space-y-0.5">
              <button v-for="nav in ['All Status', 'All Priority', 'All Category', 'All Package']" :key="nav"
                class="w-full flex items-center gap-2.5 px-3 py-2 text-[10px] font-black text-[#2E3A8C] uppercase hover:bg-white rounded-lg transition-all group">
                <i class="fas fa-folder text-slate-400 group-hover:text-blue-600 text-xs"></i>
                {{ nav }}
              </button>
            </div>
          </div>
        </aside>

        <main class="flex-1 space-y-3">
          <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 flex items-center gap-2.5 shadow-sm">
            <i class="fas fa-th-large text-[#2E3A8C] text-xs"></i>
            <span class="text-[11px] font-black text-[#2E3A8C] uppercase tracking-wider">Works Detail</span>
          </div>

          <div v-for="project in workData" :key="project.id" 
              class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm flex flex-col md:flex-row relative mb-3 transition-all hover:shadow-md">
            
            <div class="w-1 h-full absolute left-0 top-0" :class="project.color || 'bg-green-500'"></div>

            <div class="p-3 flex flex-col items-center justify-center border-r border-slate-50 md:w-26 flex-none">
              <div class="w-14 h-14 bg-white flex items-center justify-center p-1 mb-1 text-slate-200">
                <img v-if="project.logo" :src="project.logo" class="max-w-full max-h-full object-contain opacity-90" />
                <i v-else class="fas fa-image text-2xl"></i>
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
    
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
      <h3 class="text-xs font-black text-[#2E3A8C] uppercase mb-6 tracking-widest flex items-center gap-2">
        <i class="fas fa-plus-circle text-blue-600"></i>
        Add New {{ setupTab }}
      </h3>
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
          <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Name / Title</label>
          <input v-model="setupForm.name" type="text" 
            class="w-full mt-1 bg-slate-50 border-none rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-blue-100 transition-all" 
            :placeholder="'Enter ' + setupTab + ' name...'">
        </div>
        <div v-if="setupTab === 'categories'" class="md:w-48">
          <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Icon Class</label>
          <input v-model="setupForm.icon" type="text" 
            class="w-full mt-1 bg-slate-50 border-none rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-blue-100 transition-all" 
            placeholder="fas fa-folder">
        </div>
        <div class="flex items-end">
          <button @click="handleSaveMaster" 
            class="bg-blue-600 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all active:scale-95">
            Save Data
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
            <td class="px-6 py-4 text-right">
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
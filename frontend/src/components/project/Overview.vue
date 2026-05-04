<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../api/axios'; 
import WorksChart from '../Works.vue'; // Pastikan path benar

// --- STATE ---
const workData = ref<any[]>([]);
const stats = ref([
  { label: 'Total', value: '0', color: 'bg-[#6366F1]', key: 'total' },
  { label: 'Finish', value: '0', color: 'bg-[#22D3EE]', key: 'finish' },
  { label: 'Progress', value: '0', color: 'bg-[#10B981]', key: 'progress' },
  { label: 'Planing', value: '0', color: 'bg-[#FBBF24]', key: 'planing' },
  { label: 'Pending', value: '0', color: 'bg-[#EF4444]', key: 'pending' },
]);
const barSeries = ref([{ name: 'Works Status', data: [] as number[] }]);

// --- COMPUTED (Logika Grafik) ---
const donutSeriesCategory = computed(() => {
  const counts: Record<string, number> = {};
  workData.value.forEach(p => {
    const cat = p.category || 'Other';
    counts[cat] = (counts[cat] || 0) + 1;
  });
  return Object.values(counts).length ? Object.values(counts) : [0];
});

const donutSeriesPackage = computed(() => {
  const counts: Record<string, number> = {};
  workData.value.forEach(p => {
    if (p.package && p.package !== '-') counts[p.package] = (counts[p.package] || 0) + 1;
  });
  return Object.values(counts).length ? Object.values(counts) : [0];
});

const donutSeriesStatus = computed(() => {
  const counts: Record<string, number> = {};
  workData.value.forEach(p => {
    counts[p.status] = (counts[p.status] || 0) + 1;
  });
  return Object.values(counts).length ? Object.values(counts) : [0];
});

const priorityData = computed(() => {
  const priorities = ['Urgent', 'High', 'Medium', 'Low', 'Planned'];
  const counts = priorities.map(label => workData.value.filter(p => p.priority === label).length);
  return counts.some(c => c > 0) ? counts : [0, 0, 0, 0, 0];
});

// --- HELPER UNTUK GRAFIK KECIL ---
const getSeriesDataByTitle = (title: string) => {
  if (title === 'Category') return donutSeriesCategory.value;
  if (title === 'Package') return donutSeriesPackage.value;
  if (title === 'Status') return donutSeriesStatus.value;
  return [0];
};

const getLabelsByTitle = (title: string) => {
  const counts: Record<string, number> = {};
  workData.value.forEach(p => {
    const val = title === 'Category' ? p.category : title === 'Package' ? p.package : p.status;
    if (val && val !== '-') counts[val] = (counts[val] || 0) + 1;
  });
  return Object.keys(counts);
};

// --- API ACTIONS ---
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
  try {
    const res = await api.get('/projects');
    workData.value = res.data;
  } catch (e) {
    console.error("Gagal ambil data project", e);
  }
};

onMounted(() => {
  fetchStats();
  fetchProjects(); 
});
</script>

<template>
  <div class="animate-in fade-in duration-700 space-y-8 mt-4 pb-20">
    
    <!-- ROW 1: KARTU STATISTIK UTAMA (5 Kolom) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
      <div v-for="s in stats" :key="s.label" 
        class="rounded-[2.5rem] p-6 text-white shadow-xl relative overflow-hidden group transition-all duration-500 hover:-translate-y-2"
        :class="s.color">
        
        <!-- Dekorasi Lingkaran Animasi di Background -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-700"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-black/5 rounded-full -ml-8 -mb-8"></div>
        
        <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">{{ s.label }}</p>
        <h3 class="text-3xl font-black tracking-tighter">{{ s.value }}</h3>
        
        <div class="mt-4 flex items-center gap-2 text-[9px] font-bold bg-white/20 w-max px-3 py-1.5 rounded-full">
          <i class="fas fa-folder-open"></i> Projects
        </div>
      </div>
    </div>

    <!-- ROW 2: MAIN BAR CHART -->
    <div class="bg-white rounded-[3rem] p-8 shadow-sm border border-slate-200">
      <div class="flex justify-between items-center mb-8 border-b border-slate-50 pb-6">
        <div>
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Monthly Performance</h3>
          <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Project Growth & Execution Timeline</p>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
          <i class="fas fa-chart-line"></i>
        </div>
      </div>
      <div class="relative px-4">
        <WorksChart :series-data="barSeries" chart-type="bar" :height="400" />
      </div>
    </div>

    <!-- ROW 3: 3 DONUT CHARTS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <WorksChart :series-data="donutSeriesCategory" chart-type="donut" title="Works Category" />
      </div>
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <WorksChart :series-data="donutSeriesPackage" chart-type="donut" title="Works Package" />
      </div>
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <WorksChart :series-data="donutSeriesStatus" chart-type="donut" title="Works Status" />
      </div>
    </div>

    <!-- ROW 4: PRIORITY DISTRIBUTION -->
    <div class="bg-white border border-slate-200 rounded-[3rem] shadow-sm p-10 relative overflow-hidden">
      <div class="absolute top-0 right-0 p-10 opacity-[0.02] pointer-events-none">
        <i class="fas fa-exclamation-triangle text-[15rem]"></i>
      </div>

      <div class="flex justify-between items-center mb-10 border-b border-slate-50 pb-6 relative z-10">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-inner">
            <i class="fas fa-fire-alt"></i>
          </div>
          <div>
            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Priority Distribution</h3>
            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">Urgency & Importance Analysis</p>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center relative z-10">
        <div class="lg:col-span-4 bg-slate-50/50 rounded-[2.5rem] p-4 border border-slate-100">
          <WorksChart :series-data="priorityData" chart-type="donut" :height="320" />
        </div>
        <div class="lg:col-span-8">
          <WorksChart :series-data="[{ name: 'Priority', data: priorityData }]" chart-type="bar-priority" :height="350" />
        </div>
      </div>
    </div>

    <!-- ROW 5: MINI CHARTS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div v-for="title in ['Category', 'Package', 'Status']" :key="title" 
        class="bg-white border border-slate-200 rounded-[2.5rem] p-8 shadow-sm hover:shadow-md transition-all">
        <h4 class="text-center text-slate-800 font-black text-[11px] mb-6 uppercase tracking-[0.2em] border-b border-slate-50 pb-4">
          Works {{ title }}
        </h4>
        <div class="flex items-center gap-6">
          <div class="w-1/2">
            <WorksChart :series-data="getSeriesDataByTitle(title)" chart-type="donut-mini" :height="180" />
          </div>
          <div class="w-1/2 space-y-3">
            <div v-for="(name, idx) in getLabelsByTitle(title).slice(0, 4)" :key="idx" class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-xl bg-indigo-50 flex items-center justify-center text-[9px] font-black text-indigo-600 shadow-sm border border-indigo-100">{{ idx + 1 }}</span>
              <span class="text-[10px] font-bold text-slate-600 truncate uppercase tracking-tight">{{ name }}</span>
            </div>
            <p v-if="getLabelsByTitle(title).length > 4" class="text-[9px] text-slate-400 font-bold ml-9 mt-2 uppercase tracking-widest">+ More Items</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.animate-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>
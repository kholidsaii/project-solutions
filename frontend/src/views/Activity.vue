<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';

const activeTab = ref('All Projects');
const isLoading = ref(true);
const projects = ref<any[]>([]);
const searchQuery = ref('');

// 1. Ambil Data dari API
const fetchProjects = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/projects');
    projects.value = res.data;
  } catch (error) {
    console.error("Gagal mengambil data project:", error);
  } finally {
    setTimeout(() => { isLoading.value = false; }, 500);
  }
};

// 2. Logika Filter & Search
const filteredProjects = computed(() => {
  return projects.value.filter(p => {
    const matchTab = 
      activeTab.value === 'All Projects' ||
      (activeTab.value === 'In Progress' && p.progress_percent > 0 && p.progress_percent < 100) ||
      (activeTab.value === 'Completed' && p.progress_percent === 100) ||
      (activeTab.value === 'Pending' && p.progress_percent === 0);

    const matchSearch = 
      !searchQuery.value ||
      p.project_title?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.client_name?.toLowerCase().includes(searchQuery.value.toLowerCase());

    return matchTab && matchSearch;
  });
});

onMounted(fetchProjects);

const getStatusData = (progress: number) => {
  if (progress === 100) return { label: 'Completed', bg: 'bg-emerald-50 text-emerald-600' };
  if (progress === 0) return { label: 'Pending', bg: 'bg-rose-50 text-rose-600' };
  return { label: 'In Progress', bg: 'bg-blue-50 text-blue-600' };
};

const formatPrice = (value: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
};
</script>

<template>
  <div class="w-full min-h-screen bg-slate-50 pb-32 overflow-x-hidden flex flex-col items-center">
    
    <header class="w-full bg-white/80 border-b border-slate-100 px-4 py-8 md:py-12 md:px-10">
      <div class="max-w-7xl mx-auto w-full">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
          <div class="flex items-center gap-4 w-full md:w-auto">
            <div class="w-14 h-14 bg-green-500 rounded-2xl shadow-xl flex items-center justify-center text-white flex-none">
              <i class="fas fa-tasks text-xl"></i>
            </div>
            <div class="text-left">
              <h1 class="text-2xl font-black text-indigo-950 italic uppercase leading-none">Activity</h1>
              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Monitoring & Tracking</p>
            </div>
          </div>

          <div class="flex flex-col gap-3 w-full md:w-80">
            <div class="relative w-full">
              <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
              <input v-model="searchQuery" type="text" placeholder="Search..." 
                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-xs font-bold outline-none focus:ring-4 ring-green-50 transition-all">
            </div>
            <button @click="fetchProjects" class="w-full bg-white border border-slate-200 text-slate-500 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2">
              <i class="fas fa-sync-alt" :class="{'animate-spin': isLoading}"></i> Refresh
            </button>
          </div>
        </div>
      </div>
    </header>

    <div class="w-full px-4 md:px-10 mt-8">
      <div class="max-w-7xl mx-auto border-b border-slate-200 w-full overflow-hidden">
        <div class="flex gap-6 overflow-x-auto scrollbar-hide pb-4">
          <button v-for="tab in ['All Projects', 'In Progress', 'Completed', 'Pending']" :key="tab"
            @click="activeTab = tab" class="text-[10px] font-black uppercase tracking-widest transition-all relative whitespace-nowrap"
            :class="activeTab === tab ? 'text-indigo-600' : 'text-slate-400'">
            {{ tab }}
            <div v-if="activeTab === tab" class="absolute -bottom-4 left-0 w-full h-1 bg-indigo-600 rounded-t-full"></div>
          </button>
        </div>
      </div>
    </div>

    <div class="w-full px-4 md:px-10 mt-6">
      <div class="max-w-7xl mx-auto w-full">
        
        <div v-if="!isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
          <div v-for="item in filteredProjects" :key="item.id" 
            class="bg-white rounded-[2.5rem] p-6 border border-slate-100 shadow-xl shadow-slate-200/40 w-full relative overflow-hidden box-border">
            
            <div class="flex justify-between items-start mb-6">
              <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center text-white text-lg flex-none">
                <i class="fas fa-project-diagram"></i>
              </div>
              <span :class="getStatusData(item.progress_percent).bg" class="px-3 py-1 rounded-full text-[8px] font-black uppercase italic max-w-[100px] truncate">
                {{ getStatusData(item.progress_percent).label }}
              </span>
            </div>

            <div class="text-left">
              <h3 class="text-xl font-black text-indigo-950 uppercase italic leading-tight mb-1 truncate">{{ item.project_title }}</h3>
              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2 truncate">Client: {{ item.client_name }}</p>
              <p class="text-xs font-black text-emerald-600 mb-6 italic">{{ formatPrice(item.contract_value) }}</p>
            </div>

            <div class="mb-6 w-full">
              <div class="flex justify-between items-end mb-2">
                <span class="text-[8px] font-black text-slate-400 uppercase">Completion</span>
                <span class="text-xs font-black text-indigo-950">{{ item.progress_percent }}%</span>
              </div>
              <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-600" :style="{ width: item.progress_percent + '%' }"></div>
              </div>
            </div>

            <div class="flex items-center justify-between pt-5 border-t border-slate-50">
              <div class="flex flex-col text-left">
                <span class="text-[7px] font-bold text-slate-400 uppercase">Deadline</span>
                <span class="text-[9px] font-black text-slate-700 italic">{{ new Date(item.deadline).toLocaleDateString('id-ID') }}</span>
              </div>
              <router-link 
                v-if="item.category_id"
                :to="`/projects/${item.id}?cat=${item.category_id}`"
                class="w-10 h-10 bg-indigo-600 text-white rounded-xl flex items-center justify-center shadow-lg active:scale-90 transition-all"
              >
                <i class="fas fa-chevron-right text-xs"></i>
              </router-link>
            </div>
          </div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
           <div v-for="n in 3" :key="n" class="bg-white rounded-[2.5rem] p-8 h-72 animate-pulse w-full"></div>
        </div>

      </div>
    </div>
  </div>
</template>
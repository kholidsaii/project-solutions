<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';

const activeTab = ref('All Projects');
const isLoading = ref(true);
const projects = ref<any[]>([]);
const searchQuery = ref('');

// 1. Ambil Data dari API (ProjectController@index)
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
  let filtered = projects.value;

  if (searchQuery.value) {
    filtered = filtered.filter(p => 
      p.project_title?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      p.client_name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }

  if (activeTab.value === 'In Progress') {
    return filtered.filter(p => p.progress_percent > 0 && p.progress_percent < 100);
  } else if (activeTab.value === 'Completed') {
    return filtered.filter(p => p.progress_percent === 100);
  } else if (activeTab.value === 'Pending') {
    return filtered.filter(p => p.progress_percent === 0);
  }

  return filtered;
});

onMounted(fetchProjects);

// Fungsi pembantu warna status
const getStatusData = (progress: number) => {
  if (progress === 100) return { label: 'Completed', bg: 'bg-emerald-50 text-emerald-600' };
  if (progress === 0) return { label: 'Pending', bg: 'bg-rose-50 text-rose-600' };
  return { label: 'In Progress', bg: 'bg-blue-50 text-blue-600' };
};

// Format Rupiah untuk Contract Value
const formatPrice = (value: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
};
</script>

<template>
  <div class="p-6 md:p-10 min-h-screen bg-slate-50">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
      <div class="flex items-center gap-6">
        <div class="w-16 h-16 bg-green-500 rounded-3xl shadow-xl flex items-center justify-center text-white text-2xl rotate-3">
          <i class="fas fa-tasks"></i>
        </div>
        <div>
          <h1 class="text-3xl font-black text-indigo-950 italic uppercase leading-none tracking-tighter text-left">Activity</h1>
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Monitoring & Project Tracking</p>
        </div>
      </div>

      <div class="flex gap-3">
        <div class="relative">
          <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
          <input v-model="searchQuery" type="text" placeholder="Search project or client..." 
            class="pl-12 pr-6 py-3 bg-white border border-slate-100 rounded-2xl text-xs font-bold w-64 focus:ring-2 focus:ring-green-100 outline-none shadow-sm">
        </div>
        <button @click="fetchProjects" class="bg-white border border-slate-200 text-slate-500 px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
          <i class="fas fa-sync-alt mr-2" :class="{'animate-spin': isLoading}"></i> Refresh
        </button>
      </div>
    </div>

    <div class="flex gap-8 mb-8 border-b border-slate-200 overflow-x-auto">
      <button v-for="tab in ['All Projects', 'In Progress', 'Completed', 'Pending']" :key="tab"
        @click="activeTab = tab" class="pb-4 text-[10px] font-black uppercase tracking-widest transition-all relative whitespace-nowrap"
        :class="activeTab === tab ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600'">
        {{ tab }}
        <div v-if="activeTab === tab" class="absolute bottom-0 left-0 w-full h-1 bg-indigo-600 rounded-t-full"></div>
      </button>
    </div>

    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div v-for="n in 3" :key="n" class="bg-white rounded-[2.5rem] p-8 h-64 animate-pulse border border-slate-100"></div>
    </div>

    <div v-else-if="filteredProjects.length === 0" class="flex flex-col items-center justify-center py-20 text-slate-300">
      <i class="fas fa-folder-open text-5xl mb-4 opacity-20"></i>
      <p class="font-bold uppercase tracking-widest text-[10px]">No activities found in this category</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div v-for="item in filteredProjects" :key="item.id" 
        class="bg-white rounded-[2.5rem] p-8 border border-slate-50 shadow-xl shadow-slate-200/50 hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        
        <div class="flex justify-between items-start mb-6">
          <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white text-xl shadow-lg shadow-indigo-100">
            <i class="fas fa-project-diagram"></i>
          </div>
          <span :class="getStatusData(item.progress_percent).bg" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase italic">
            {{ getStatusData(item.progress_percent).label }}
          </span>
        </div>

        <h3 class="text-lg font-black text-indigo-950 uppercase italic leading-tight mb-1">
          {{ item.project_title }}
        </h3>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
          Client: {{ item.client_name }}
        </p>
        <p class="text-xs font-black text-emerald-600 mb-6 italic">{{ formatPrice(item.contract_value) }}</p>

        <div class="mb-6">
          <div class="flex justify-between items-end mb-2">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Progress</span>
            <span class="text-xs font-black text-indigo-950">{{ item.progress_percent }}%</span>
          </div>
          <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full bg-indigo-600 transition-all duration-1000" :style="{ width: item.progress_percent + '%' }"></div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-slate-50">
          <div class="flex flex-col text-left">
            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter">Target Deadline</span>
            <span class="text-[10px] font-black text-slate-700 italic">{{ new Date(item.deadline).toLocaleDateString('id-ID') }}</span>
          </div>
         <router-link 
  v-if="item.category_id"
  :to="`/projects/${item.id}?cat=${item.category_id}`"
  class="w-10 h-10 bg-indigo-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all cursor-pointer"
>
  <i class="fas fa-chevron-right text-xs"></i>
</router-link>

<div v-else class="w-10 h-10 bg-slate-100 text-slate-300 rounded-xl flex items-center justify-center cursor-help" title="Category belum di-set">
  <i class="fas fa-exclamation-triangle text-xs"></i>
</div>
        </div>
      </div>
    </div>
  </div>
</template> 
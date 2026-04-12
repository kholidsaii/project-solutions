<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api/axios';
import Works from '../components/Works.vue';

const isLoading = ref(true);
const barSeries = ref<any[]>([]);
const donutSeriesCat = ref<number[]>([]);
const donutSeriesStatus = ref<number[]>([]);
const summary = ref({ total: 0, finish: 0, progress: 0, planing: 0, pending: 0 });

const fetchAnalysisData = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/works/stats');
    if (res?.data) {
      summary.value = res.data.summary;
      barSeries.value = [{ name: 'Project Growth', data: res.data.monthly || [] }];
      donutSeriesCat.value = res.data.categories || [0, 0, 0];
      donutSeriesStatus.value = [
        res.data.summary.finish, 
        res.data.summary.progress, 
        res.data.summary.planing, 
        res.data.summary.pending
      ];
    }
  } catch (error) {
    console.error("Gagal ambil data analisis:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchAnalysisData);
</script>

<template>
  <div class="p-4 md:p-10 min-h-screen bg-slate-50">
    <div class="flex flex-col md:flex-row justify-between items-center mb-10">
        <div class="flex items-center gap-6">
            <div class="w-16 h-16 bg-indigo-600 rounded-3xl shadow-xl flex items-center justify-center text-white text-2xl rotate-6">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black text-indigo-950 italic uppercase leading-none tracking-tighter text-left">Data Analysis</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Performance & Growth Metrics</p>
            </div>
        </div>
        <button @click="fetchAnalysisData" class="p-4 bg-white rounded-2xl border border-slate-100 shadow-sm text-indigo-600 hover:bg-indigo-50">
            <i class="fas fa-sync-alt" :class="{'animate-spin': isLoading}"></i>
        </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-10">
        <div v-for="(val, key) in summary" :key="key" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ key }}</p>
            <p class="text-2xl font-black text-indigo-950 mt-1">{{ val }}</p>
        </div>
    </div>

    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 gap-8">
       <div v-for="i in 4" :key="i" class="h-80 bg-white rounded-[3rem] animate-pulse"></div>
    </div>

    <div v-else class="space-y-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
          <Works :series-data="barSeries" chart-type="bar">
            <template #title>
              <h2 class="text-sm font-black text-slate-800 uppercase italic mb-4">Monthly Project Completion</h2>
            </template>
          </Works>
        </div>
        
        <div class="rounded-[3rem] p-10 bg-indigo-950 text-white shadow-xl flex flex-col justify-between relative overflow-hidden">
          <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4">
              <span class="w-2 h-2 bg-emerald-400 rounded-full animate-ping"></span>
              <h3 class="text-[10px] font-black uppercase text-emerald-400 tracking-widest">System Health</h3>
            </div>
            <p class="text-6xl font-black italic tracking-tighter">98.2%</p>
            <p class="text-[10px] font-bold text-white/50 mt-6 uppercase italic leading-relaxed">Semua sistem berjalan normal <br> tanpa kendala hari ini.</p>
          </div>
          <button class="w-full py-5 bg-white text-indigo-950 rounded-2xl font-black text-[10px] uppercase shadow-xl mt-10">View Full Report</button>
          <i class="fas fa-shield-alt absolute -bottom-10 -right-10 text-[15rem] text-white/5 rotate-12"></i>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <Works :series-data="donutSeriesCat" chart-type="donut">
          <template #title><h3 class="text-xs font-black text-slate-400 uppercase italic mb-4 text-left">Market Segmentation</h3></template>
        </Works>
        <Works :series-data="donutSeriesStatus" chart-type="donut">
          <template #title><h3 class="text-xs font-black text-slate-400 uppercase italic mb-4 text-left">Project Status Distribution</h3></template>
        </Works>
      </div>
    </div>
  </div>
</template>
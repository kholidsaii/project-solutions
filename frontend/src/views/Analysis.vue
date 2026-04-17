<script setup lang="ts">
import { ref, onMounted } from 'vue';
import VueApexCharts from "vue3-apexcharts";
import api from '../api/axios';
import type { ApexOptions } from 'apexcharts';

const currentTab = ref('overview');
const isLoading = ref(true);

const stats = ref([
  { label: 'Total', value: '0', color: 'bg-indigo-600', key: 'total' },
  { label: 'Finish', value: '0', color: 'bg-sky-400', key: 'finish' },
  { label: 'Progress', value: '0', color: 'bg-green-500', key: 'progress' },
  { label: 'Planing', value: '0', color: 'bg-amber-400', key: 'planing' },
  { label: 'Pending', value: '0', color: 'bg-rose-500', key: 'pending' },
]);

// Inisialisasi dengan data kosong agar tidak undefined saat diakses di fetch
const barSeries = ref<{ name: string; data: number[] }[]>([{ name: 'Data', data: [] }]);
const donutSeriesCat = ref<number[]>([25, 25, 25, 25]);
const donutSeriesPack = ref<number[]>([30, 20, 25, 25]);

const fetchWorksData = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/works/stats');
    if (res.data) {
      // Update Stats
      stats.value.forEach(s => {
        if (res.data.summary[s.key] !== undefined) {
          s.value = res.data.summary[s.key].toLocaleString();
        }
      });
      
      // FIX: Gunakan pengecekan null/undefined yang aman
      if (barSeries.value[0]) {
        barSeries.value[0].data = res.data.monthly || [];
      }
      
      donutSeriesCat.value = res.data.categories || [25, 28, 19, 28];
    }
  } catch (error) {
    console.error("Gagal ambil data real:", error);
  } finally {
    setTimeout(() => { isLoading.value = false; }, 500);
  }
};

const barOptions: ApexOptions = {
  chart: { toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
  colors: ['#6366f1', '#22d3ee', '#10b981', '#f59e0b', '#ef4444'],
  plotOptions: { bar: { borderRadius: 8, columnWidth: '50%', distributed: true } },
  xaxis: { 
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
    labels: { style: { fontSize: '10px', fontWeight: 800, colors: '#94a3b8' } }
  },
  dataLabels: { enabled: false },
  grid: { borderColor: '#f1f5f9' }
};

const donutOptions: ApexOptions = {
  labels: ['Retail', 'Project', 'Walk In', 'Member'],
  colors: ['#10b981', '#6366f1', '#f59e0b', '#ef4444'],
  legend: { position: 'bottom', fontWeight: 700 },
  dataLabels: { enabled: false },
  plotOptions: { pie: { donut: { size: '75%' } } }
};

onMounted(fetchWorksData);
</script>

<template>
  <div class="p-4 md:p-8 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
      <div class="flex items-center gap-6">
        <div class="w-16 h-16 bg-rose-500 rounded-3xl shadow-xl shadow-rose-200 flex items-center justify-center text-white text-2xl animate-pulse">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div>
          <h1 class="text-3xl font-black text-indigo-950 italic uppercase leading-none tracking-tighter">WORKS</h1>
          <div class="flex gap-6 mt-3">
            <button @click="currentTab = 'overview'" 
              :class="currentTab === 'overview' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400'" 
              class="text-[10px] font-black uppercase tracking-[0.2em] pb-1 transition-all">
              OVERVIEW
            </button>
            <button @click="currentTab = 'data'" 
              :class="currentTab === 'data' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400'" 
              class="text-[10px] font-black uppercase tracking-[0.2em] pb-1 transition-all">
              DATA DETAIL
            </button>
          </div>
        </div>
      </div>
      
      <button @click="fetchWorksData" class="px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-50 transition-all">
        <i class="fas fa-sync-alt mr-2" :class="{'animate-spin': isLoading}"></i> Refresh Data
      </button>
    </div>

    <div v-if="currentTab === 'overview'" class="space-y-8">
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 md:gap-6">
        <div v-for="s in stats" :key="s.label" :class="s.color" class="p-6 rounded-[2.5rem] text-white shadow-xl shadow-slate-200 transition-transform hover:scale-105">
          <p class="text-[9px] font-bold uppercase opacity-70 tracking-widest">{{ s.label }}</p>
          <div v-if="isLoading" class="h-8 w-16 bg-white/20 animate-pulse rounded-lg mt-1"></div>
          <p v-else class="text-2xl font-black mt-1">{{ s.value }}</p>
        </div>
      </div>

      <div class="bg-white p-6 md:p-8 rounded-[3rem] border border-slate-100 shadow-sm">
        <div class="flex items-center justify-between mb-8">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center">
              <i class="fas fa-chart-bar"></i>
            </div>
            <h2 class="text-sm font-black text-slate-800 uppercase italic">Monthly Progress Analysis</h2>
          </div>
        </div>
        <div v-if="isLoading" class="h-75 flex items-center justify-center bg-slate-50 rounded-2xl italic text-slate-400 font-bold uppercase text-xs tracking-widest">
           Generating Analysis...
        </div>
        <VueApexCharts v-else height="320" type="bar" :options="barOptions" :series="barSeries" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm">
          <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 italic">Works by Category</h3>
          <VueApexCharts type="donut" height="280" :options="donutOptions" :series="donutSeriesCat" />
        </div>
        <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm">
          <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 italic">Works by Package</h3>
          <VueApexCharts type="donut" height="280" :options="donutOptions" :series="donutSeriesPack" />
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <div v-for="i in 6" :key="i" class="bg-white p-6 rounded-4xl border border-slate-100 flex items-center justify-between hover:border-indigo-200 transition-all group">
        <div class="flex items-center gap-5">
          <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-all">
            <i class="fas fa-file-invoice text-xl"></i>
          </div>
          <div>
            <div :class="isLoading ? 'w-48 h-4 bg-slate-100 animate-pulse rounded' : ''" class="text-sm font-black text-slate-700 uppercase italic">
              {{ isLoading ? '' : 'Project Development Website RS Suyoto #' + (1000 + i) }}
            </div>
            <div :class="isLoading ? 'w-32 h-3 bg-slate-50 animate-pulse rounded mt-2' : ''" class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1">
              {{ isLoading ? '' : 'Client: Hospital Management System' }}
            </div>
          </div>
        </div>
        <div v-if="!isLoading" class="flex items-center gap-4">
          <span class="px-4 py-1.5 bg-emerald-50 text-emerald-600 text-[9px] font-black rounded-full uppercase italic">Completed</span>
          <button class="w-10 h-10 bg-slate-50 rounded-xl text-slate-300 hover:bg-indigo-600 hover:text-white transition-all">
            <i class="fas fa-chevron-right text-xs"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8 space-y-4">
      
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="p-4 flex items-center justify-between border-b border-slate-100">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-white rounded-lg border border-slate-200 flex items-center justify-center p-2">
              <img src="/logo-kerjapro.png" class="max-w-full max-h-full object-contain" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">ID-{{ $route.params.id }}</span>
                <h2 class="text-sm font-black text-blue-800 uppercase leading-none">{{ project?.project_title || 'Loading...' }}</h2>
              </div>
              <p class="text-[10px] font-bold text-slate-600 uppercase mt-0.5">Customer : {{ project?.client_name }}</p>
              <p class="text-[8px] text-slate-400 italic">Crate by : Suhery Solutions 10/11/2024</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
             <button @click="$router.push('/projects')" class="bg-slate-800 text-white px-4 py-1.5 rounded-lg text-[10px] font-bold flex items-center gap-2">
               <i class="fas fa-home"></i> About
             </button>
             <div class="px-4 py-1.5 bg-blue-50 text-blue-700 rounded-lg border border-blue-100 font-black text-xs">
               {{ project?.progress }} %
             </div>
          </div>
        </div>

        <div class="bg-slate-50 px-4 py-1.5 flex flex-wrap gap-1 border-b border-slate-100 items-center">
          <button @click="$router.back()" class="w-7 h-7 bg-slate-400 text-white rounded-md flex items-center justify-center text-[10px]">
            <i class="fas fa-arrow-left"></i>
          </button>
          <button v-for="menu in ['Overview', 'Aktivty', 'Workorder', 'Teamwork', 'Produks', 'Document', 'Support', 'Marketing', 'Purchasing', 'Financial', 'Accounting']" 
            :key="menu"
            @click="subTab = menu.toLowerCase()"
            class="px-3 py-1.5 text-[9px] font-black uppercase transition-all"
            :class="subTab === menu.toLowerCase() ? 'text-blue-800 underline underline-offset-4 decoration-2' : 'text-blue-600 hover:bg-blue-50'">
            {{ menu }}
          </button>
          <button class="ml-auto flex items-center gap-1 text-[9px] font-black uppercase text-slate-600">
            <i class="fas fa-cog"></i> Setup
          </button>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-h-[400px]">
        <div class="flex justify-between items-center px-6 py-3 border-b border-slate-100">
          <div class="flex items-center gap-3 text-blue-900">
            <i class="fas fa-th-large text-sm"></i>
            <h3 class="text-xs font-black uppercase tracking-widest">{{ subTab }}</h3>
          </div>
          <i class="fas fa-calendar-alt text-slate-200 text-xl"></i>
        </div>

        <div class="p-8">
          <div v-if="subTab === 'overview'" class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div class="space-y-8">
              <div>
                <h4 class="text-sm font-black text-blue-800 mb-4">About</h4>
                <div class="pl-6 space-y-1.5">
                  <div class="flex text-[10px] uppercase font-black"><span class="w-32 text-blue-700">Name Work</span><span>: {{ project?.project_title }}</span></div>
                  <div class="flex text-[10px] uppercase font-black"><span class="w-32 text-blue-700">Crate By</span><span>: Suhery</span></div>
                  <div class="flex text-[10px] uppercase font-black"><span class="w-32 text-blue-700">Crate Date</span><span>: 10/11/2024</span></div>
                </div>
              </div>
              <div>
                <h4 class="text-sm font-black text-blue-800 mb-2">Customer</h4>
                <p class="pl-6 text-[10px] font-black text-slate-800 uppercase">{{ project?.client_name }}</p>
              </div>
              <div>
                <h4 class="text-sm font-black text-blue-800 mb-2">Target</h4>
                <p class="pl-6 text-[10px] font-black text-slate-800 uppercase">-</p>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-3 self-start">
              <div v-for="color in ['bg-indigo-500', 'bg-green-400', 'bg-amber-400', 'bg-cyan-300', 'bg-rose-500', 'bg-red-600']" 
                :key="color" :class="color" class="h-16 rounded-xl shadow-sm"></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';

const route = useRoute();
const subTab = ref('overview');
const project = ref<any>(null);

const fetchDetail = async () => {
  try {
    const id = route.params.id;
    // Panggil API spesifik detail project
    const res = await api.get(`/projects/${id}`);
    project.value = res.data;
  } catch (e) {
    console.error("Gagal ambil detail", e);
  }
};

onMounted(fetchDetail);
</script>
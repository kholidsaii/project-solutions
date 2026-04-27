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
          <div v-if="subTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-10">
  
            <div class="lg:col-span-2 space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-[11px] font-black text-blue-800 mb-4 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Project Identity</h4>
                    <div class="space-y-3 pl-4">
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Project Name</span>
                        <input v-model="project.project_title" @change="updateDetail" class="text-[11px] font-black text-slate-800 uppercase bg-transparent border-b border-transparent hover:border-slate-200 focus:border-blue-500 outline-none transition-all py-1">
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Customer / Client</span>
                        <input v-model="project.client_name" @change="updateDetail" class="text-[11px] font-black text-slate-800 uppercase bg-transparent border-b border-transparent hover:border-slate-200 focus:border-blue-500 outline-none transition-all py-1">
                    </div>
                    </div>
                </div>

                <div>
                    <h4 class="text-[11px] font-black text-blue-800 mb-4 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Timeline & Value</h4>
                    <div class="space-y-3 pl-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Start Date</span>
                        <input type="date" v-model="project.start_date" @change="updateDetail" class="text-[10px] font-black text-slate-700 outline-none bg-slate-50 rounded-md px-2 py-1 mt-1">
                        </div>
                        <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Finish Date</span>
                        <input type="date" v-model="project.finish_date" @change="updateDetail" class="text-[10px] font-black text-slate-700 outline-none bg-slate-50 rounded-md px-2 py-1 mt-1">
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Contract Value</span>
                        <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] font-black text-slate-400">IDR</span>
                        <input type="number" v-model="project.contract_value" @change="updateDetail" class="text-[11px] font-black text-emerald-600 outline-none bg-transparent border-b border-slate-100 w-full">
                        </div>
                    </div>
                    </div>
                </div>
                </div>

                <div>
                <h4 class="text-[11px] font-black text-blue-800 mb-3 uppercase tracking-widest border-l-4 border-blue-600 pl-3">Project Description</h4>
                <textarea v-model="project.description" @change="updateDetail" rows="3" class="w-full pl-4 text-[11px] font-medium text-slate-600 bg-transparent border-none focus:ring-0 resize-none uppercase" placeholder="ADD DESCRIPTION..."></textarea>
                </div>
            </div>

            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 space-y-6">
                <h4 class="text-[11px] font-black text-blue-900 uppercase tracking-tighter text-center mb-4">Work Configuration</h4>
                
                <div class="space-y-4">
                <div class="flex flex-col">
                    <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Current Status</label>
                    <select v-model="project.status" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-blue-800 uppercase outline-none focus:ring-2 ring-blue-100">
                    <option v-for="s in masterData.status" :key="s.id" :value="s.name">{{ s.name }}</option>
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Priority Level</label>
                    <select v-model="project.priority" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-rose-600 uppercase outline-none focus:ring-2 ring-rose-100">
                    <option v-for="p in masterData.priority" :key="p.id" :value="p.name">{{ p.name }}</option>
                    </select>
                </div>

                <div class="flex flex-col">
                    <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Contract Package</label>
                    <select v-model="project.package" @change="updateDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-slate-700 uppercase outline-none">
                    <option v-for="pkg in masterData.package" :key="pkg.id" :value="pkg.name">{{ pkg.name }}</option>
                    </select>
                </div>
                </div>

                <div class="pt-6 border-t border-slate-200 grid grid-cols-2 gap-4">
                <div class="text-center">
                    <p class="text-[8px] font-bold text-slate-400 uppercase">Days Elapsed</p>
                    <p class="text-sm font-black text-slate-800">{{ project?.total_day || '0' }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[8px] font-bold text-slate-400 uppercase">Tasks Count</p>
                    <p class="text-sm font-black text-slate-800">{{ project?.tasks?.length || '0' }}</p>
                </div>
                </div>
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
const project = ref<any>({
  project_title: '',
  client_name: '',
  status: '',
  priority: '',
  package: '',
  start_date: '',
  finish_date: '',
  description: ''
});

const masterData = ref({
  status: [],
  priority: [],
  package: []
});

const fetchMaster = async () => {
  try {
    const res = await api.get('/get-master-data');
    masterData.value = res.data;
  } catch (e) {
    console.error("Gagal load master", e);
  }
}

const fetchDetail = async () => {
  try {
    const id = route.params.id;
    const res = await api.get(`/projects/${id}`);
    project.value = res.data;
  } catch (e) {
    console.error("Gagal ambil detail", e);
  }
};

// FUNGSI AUTO-SAVE: Dipanggil setiap ada perubahan input
const updateDetail = async () => {
  try {
    const id = route.params.id;
    // Kita gunakan endpoint updateMaster tapi tipenya 'projects'
    await api.post(`/master-data/projects/${id}`, {
      ...project.value,
      name: project.value.project_title, // Map ke field name jika controller butuh
      _method: 'PUT'
    });
    console.log("Detail updated automatically");
  } catch (e) {
    console.error("Gagal update otomatis", e);
  }
};

onMounted(async () => {
  await fetchMaster();
  await fetchDetail();
});
</script>
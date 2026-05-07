<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios'; // Pastikan path axios-mu benar

// Import semua components
import Overview from '../components/project/detail/Overview.vue';
import Activity from '../components/project/detail/Activity.vue';
import Location from '../components/project/detail/Location.vue';
import Workorder from '../components/project/detail/Workorder.vue';
import Product from '../components/project/detail/Product.vue';
import Teamwork from '../components/project/detail/Teamwork.vue'; 
import Financial from '../components/project/detail/Financial.vue';
import Accounting from '../components/project/detail/Accounting.vue';

const route = useRoute();
const subTab = ref('overview');
// Tab disesuaikan (dikurangi document, support, marketing, purchasing)
const availableTabs = ['Overview', 'Workorder', 'Activity', 'Location', 'Product', 'Teamwork', 'Financial', 'Accounting'];

const project = ref<any>({});
const allCompanies = ref<any[]>([]);
const masterData = ref({ categories: [], status: [], priority: [], package: [] });

const fetchMaster = async () => {
  try {
    const res = await api.get('/get-master-data');
    masterData.value = res.data;
  } catch (e) { console.error("Gagal load master", e); }
};

const fetchDetail = async () => {
  try {
    const res = await api.get(`/projects/${route.params.id}`);
    project.value = res.data;
  } catch (e) { console.error("Gagal ambil detail", e); }
};

onMounted(async () => {
  await fetchMaster();
  await fetchDetail();
  try {
    const resComp = await api.get('/companies');
    allCompanies.value = resComp.data;
  } catch (e) { console.error(e); }
});
</script>
<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8 space-y-4">   
      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
        <div class="p-4 flex items-center justify-between border-b border-slate-100">
          <div class="flex items-center gap-4">
  
            <div class="w-12 h-12 bg-white rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden">
              <img v-if="project?.logo" 
                  :src="`http://localhost:8000/uploads/${project.logo}`" 
                  class="w-full h-full object-cover" />
                  
              <img v-else 
                  src="/logo-kerjapro.png" 
                  class="max-w-full max-h-full object-contain p-2" />
            </div>

            <div>
              <div class="flex items-center gap-2">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">ID-{{ $route.params.id }}</span>
                <h2 class="text-sm font-black text-blue-800 uppercase leading-none">{{ project?.project_title || 'Loading...' }}</h2>
              </div>
              
              <p class="text-[10px] font-bold text-slate-600 uppercase mt-0.5 flex items-center gap-1.5 flex-wrap">
                <span>PT:</span> 
                
                <span class="text-indigo-600 font-black">
                  <template v-if="project?.companies && project.companies.length">
                    {{ project.companies.map((c: any) => c.name).join(', ') }}
                  </template>
                  <template v-else>
                    INDEPENDENT
                  </template>
                </span> 
                
                <span class="text-slate-400 ml-1">| Customer : {{ project?.client_name || '-' }}</span>
              </p>
            </div>
            
          </div>
          <div class="flex items-center gap-3">
             <button @click="$router.push('/projects')" class="bg-slate-800 text-white px-4 py-1.5 rounded-lg text-[10px] font-bold flex items-center gap-2">
               <i class="fas fa-home"></i> Back
             </button>
             <div class="px-4 py-1.5 bg-blue-50 text-blue-700 rounded-lg border border-blue-100 font-black text-xs">
               {{ project?.progress || 0 }} %
             </div>
          </div>
        </div>

        <div class="bg-slate-50 px-4 py-1.5 flex flex-wrap gap-1 border-b border-slate-100 items-center">
          <button @click="$router.push('/projects')" class="w-7 h-7 bg-slate-400 text-white rounded-md flex items-center justify-center text-[10px]">
            <i class="fas fa-arrow-left"></i>
          </button>
          <button v-for="menu in availableTabs" :key="menu"
            @click="subTab = menu.toLowerCase()"
            class="px-3 py-1.5 text-[9px] font-black uppercase transition-all"
            :class="subTab === menu.toLowerCase() ? 'text-blue-800 underline underline-offset-4 decoration-2' : 'text-blue-600 hover:bg-blue-50'">
            {{ menu }}
          </button>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden min-h-100">
        <div class="flex justify-between items-center px-6 py-3 border-b border-slate-100">
          <div class="flex items-center gap-3 text-blue-900">
            <i class="fas fa-th-large text-sm"></i><h3 class="text-xs font-black uppercase tracking-widest">{{ subTab }}</h3>
          </div>
        </div>

        <div class="p-8">
          <Overview v-if="subTab === 'overview'" :project="project" :masterData="masterData" :allCompanies="allCompanies" @refresh="fetchDetail" />
          <Activity v-if="subTab === 'activity'" :project="project" @refresh="fetchDetail" />
          <Location v-if="subTab === 'location'" :project="project" @refresh="fetchDetail" />
          <Workorder v-if="subTab === 'workorder'" :project="project" @refresh="fetchDetail" />
          <Product v-if="subTab === 'product'" :project="project" @refresh="fetchDetail" />
          <Teamwork v-if="subTab === 'teamwork'" :project="project" @refresh="fetchDetail" />
          <Financial v-if="subTab === 'financial'" :project="project" @refresh="fetchDetail" />
          <Accounting v-if="subTab === 'accounting'" :project="project" :allCompanies="allCompanies" @refresh="fetchDetail" />
        </div>
      </div>
    </div>
  </div>
</template>


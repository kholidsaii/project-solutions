<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';

// 1. IMPORT SEMUA COMPONENTS DARI FOLDER ORGDETAIL
import OrgOverview from '../components/project/orgdetail/orgOverview.vue';
import OrgProject from '../components/project/orgdetail/orgProject.vue';
import OrgTransaksi from '../components/project/orgdetail/orgTransaksi.vue';
import OrgAccounting from '../components/project/orgdetail/orgAccounting.vue';
import OrgBanking from '../components/project/orgdetail/orgBanking.vue';
import OrgSetup from '../components/project/orgdetail/orgSetup.vue';

const route = useRoute();
// const router = useRouter();

// Tab aktif secara default
const subTab = ref('overview');

// 2. DAFTAR TAB (Menyesuaikan dengan komponen yang ada di orgdetail)
const availableTabs = [
  { name: 'Overview', value: 'overview', icon: 'fas fa-building' },
  { name: 'Project', value: 'project', icon: 'fas fa-project-diagram' },
  { name: 'Transaksi', value: 'transaksi', icon: 'fas fa-exchange-alt' },
  { name: 'Accounting', value: 'accounting', icon: 'fas fa-book' },
  { name: 'Banking', value: 'banking', icon: 'fas fa-university' }
];

const companyData = ref<any>({});
const isLoading = ref(true);

const fetchDetail = async () => {
  isLoading.value = true;
  try {
    // Memanggil API khusus company
    const res = await api.get(`/companies/${route.params.id}`);
    
    // Sama seperti MemberDetail, antisipasi wrapper 'data' dari Laravel
    companyData.value = res.data.data || res.data;
    
  } catch (e) { 
    console.error("Gagal mengambil detail company", e); 
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchDetail();
});

const getImageUrl = (path: string | null): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith('data:') || path.startsWith('http')) return path;
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'; 
  const baseUrl = apiUrl.replace('/api', '');
  let cleanPath = path.startsWith('/') ? path.substring(1) : path;
  return `${baseUrl}/uploads/${cleanPath}`;
};
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans text-slate-900 overflow-x-hidden w-full max-w-[100vw]">
    <div class="w-full max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 mt-6 sm:mt-8 space-y-5 min-w-0">   
      
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm w-full overflow-hidden min-w-0 relative">
        <div v-if="isLoading" class="absolute inset-0 bg-white/50 backdrop-blur-sm z-50 flex items-center justify-center">
            <i class="fas fa-spinner fa-spin text-3xl text-indigo-600"></i>
        </div>

        <div class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 min-w-0">
          <div class="flex items-center gap-4 min-w-0 flex-1">
            <div class="w-12 h-12 shrink-0 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-center overflow-hidden p-1">
              <img v-if="companyData?.logo_path" :src="getImageUrl(companyData.logo_path)" class="w-full h-full object-contain" />
              <div v-else class="w-full h-full flex items-center justify-center text-indigo-400 font-black text-lg uppercase bg-indigo-50 rounded">
                 <i class="fas fa-building text-slate-300"></i>
              </div>
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[9px] font-black uppercase tracking-widest shrink-0">ORG-{{ $route.params.id }}</span>
                <h2 class="text-sm font-black text-slate-800 uppercase leading-none truncate">{{ companyData?.name || 'Loading...' }}</h2>
              </div>
              
              <p class="text-[10px] font-bold text-slate-500 uppercase mt-1 truncate">
                <span class="text-indigo-600 font-black">Corporate Partner</span> 
                <span class="text-slate-300 mx-1">|</span> 
                <span><i class="fas fa-envelope text-slate-400 mr-1"></i> {{ companyData?.email || '-' }}</span>
                <span class="text-slate-300 mx-1">|</span> 
                <span><i class="fas fa-phone-alt text-slate-400 mr-1"></i> {{ companyData?.phone || '-' }}</span>
              </p>
            </div>
          </div>
          
          <div class="flex items-center gap-3 shrink-0">
             <div class="px-4 py-2 bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-100 font-black text-xs flex items-center gap-2 capitalize">
               <i class="fas fa-check-circle text-[10px]"></i> Active
             </div>
             
             <button @click="subTab = 'setup'" title="Organization Setup"
               class="w-9 h-9 flex items-center justify-center rounded-lg border transition-all"
               :class="subTab === 'setup' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-indigo-600 border-slate-200'">
               <i class="fas fa-cog"></i>
             </button>
          </div>
        </div>

        <div class="bg-white px-4 py-3 flex gap-3 border-b border-slate-100 items-center w-full min-w-0">
          <button @click="$router.push('/projects')" class="shrink-0 w-9 h-9 bg-slate-800 hover:bg-slate-700 text-white rounded-xl flex items-center justify-center text-xs transition-colors shadow-sm">
            <i class="fas fa-arrow-left"></i>
          </button>
          
          <div class="flex-1 min-w-0 overflow-x-auto custom-h-scroll pb-2">
            <div class="flex gap-2 flex-nowrap w-max pr-4">
              <button v-for="tab in availableTabs" :key="tab.value"
                @click="subTab = tab.value"
                class="shrink-0 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 transition-all duration-300"
                :class="subTab === tab.value 
                  ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200 scale-[1.02]' 
                  : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-indigo-600'">
                <i :class="tab.icon" class="text-sm"></i> {{ tab.name }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-xl shadow-sm w-full overflow-hidden min-h-[500px] min-w-0">
        <div class="flex justify-between items-center px-6 py-4 border-b border-slate-100 bg-slate-50/50 min-w-0">
          <div class="flex items-center gap-3 text-indigo-900 min-w-0">
            <template v-if="subTab === 'setup'">
               <i class="fas fa-cogs text-lg shrink-0"></i>
               <h3 class="text-xs font-black uppercase tracking-widest truncate">Organization Settings</h3>
            </template>
            <template v-else>
               <i :class="availableTabs.find(t => t.value === subTab)?.icon" class="text-lg shrink-0"></i>
               <h3 class="text-xs font-black uppercase tracking-widest truncate">{{ availableTabs.find(t => t.value === subTab)?.name }}</h3>
            </template>
          </div>
        </div>

        <div class="p-4 sm:p-6 lg:p-8 w-full min-w-0 overflow-x-auto">
          <OrgOverview v-if="subTab === 'overview'" :companyData="companyData" @refresh="fetchDetail" />
          <OrgProject v-if="subTab === 'project'" :companyData="companyData" @refresh="fetchDetail" />
          <OrgTransaksi v-if="subTab === 'transaksi'" :companyData="companyData" @refresh="fetchDetail" />
          <OrgAccounting v-if="subTab === 'accounting'" :companyData="companyData" @refresh="fetchDetail" />
          <OrgBanking v-if="subTab === 'banking'" :companyData="companyData" @refresh="fetchDetail" />
          <OrgSetup v-if="subTab === 'setup'" :companyData="companyData" @refresh="fetchDetail" />
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.custom-h-scroll::-webkit-scrollbar { height: 4px; }
.custom-h-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-h-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
.custom-h-scroll::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>
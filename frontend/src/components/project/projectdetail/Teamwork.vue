<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  project: any
}>();

// Data otomatis diambil dari props hasil fetch backend
const projectCompanies = computed(() => props.project?.companies || []);
const projectTeam = computed(() => props.project?.team || []);

// Helper Image
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
  <div class="space-y-8 animate-in fade-in duration-500">
    
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-slate-200">
      <div>
        <h3 class="text-sm font-black text-slate-800 uppercase">Project Resources</h3>
        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">Organisasi dan Anggota yang terlibat di ID-{{ project?.id }}</p>
      </div>
      <div class="flex gap-2">
        <span class="bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg text-xs font-bold border border-indigo-100 flex items-center gap-1.5">
          <i class="fas fa-lock"></i> View Only
        </span>
      </div>
    </div>

    <div>
      <h4 class="text-xs font-black text-slate-600 uppercase mb-4 flex items-center gap-2">
        <i class="fas fa-building text-indigo-500"></i> Affiliated Organizations
        <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded text-[10px]">{{ projectCompanies.length }}</span>
      </h4>
      
      <div v-if="projectCompanies.length === 0" class="p-8 border-2 border-dashed border-slate-200 rounded-xl text-center">
        <p class="text-xs font-bold text-slate-400 uppercase">Belum ada PT yang terhubung ke project ini.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-for="cp in projectCompanies" :key="cp.id" class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden flex flex-col relative group">
          
          <div class="h-16 w-full bg-slate-100 relative overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center opacity-80" 
                 :style="{ backgroundImage: `url(${cp.cover_image ? getImageUrl(cp.cover_image) : (cp.cover_url || 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?q=80&w=500')})` }">
            </div>
          </div>
          
          <div class="flex justify-center -mt-8 relative z-10">
            <div class="w-16 h-16 bg-white rounded-full p-1 shadow-sm border border-slate-100 flex items-center justify-center overflow-hidden">
              <img v-if="cp.logo_path" :src="getImageUrl(cp.logo_path)" class="w-full h-full object-contain rounded-full">
              <i v-else class="fas fa-building text-2xl text-slate-300"></i> 
            </div>
          </div>
          
          <div class="p-4 flex flex-col items-center flex-1">
             <h4 class="font-black text-[12px] text-[#2B3674] uppercase text-center w-full leading-tight">{{ cp.name }}</h4>
             <span class="text-[9px] font-bold px-2 py-0.5 mt-1 bg-emerald-50 text-emerald-600 rounded uppercase">Partner</span>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-8">
      <h4 class="text-xs font-black text-slate-600 uppercase mb-4 flex items-center gap-2">
        <i class="fas fa-users text-indigo-500"></i> Project Members
        <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded text-[10px]">{{ projectTeam.length }}</span>
      </h4>

      <div v-if="projectTeam.length === 0" class="p-8 border-2 border-dashed border-slate-200 rounded-xl text-center">
        <p class="text-xs font-bold text-slate-400 uppercase">Belum ada anggota tim karena belum ada PT yang terhubung.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="m in projectTeam" :key="m.id" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="w-10 h-10 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600 font-black text-sm uppercase shrink-0 overflow-hidden">
             <img v-if="m.avatar_url" :src="getImageUrl(m.avatar_url)" class="w-full h-full object-cover" />
             <span v-else>{{ m.name.substring(0, 2) }}</span>
          </div>
          <div class="overflow-hidden">
            <h5 class="text-xs font-black text-slate-800 uppercase truncate">{{ m.name }}</h5>
            <p class="text-[9px] font-bold text-slate-500 truncate">
              {{ m.position || 'Staff' }} • <span class="text-indigo-500 uppercase">{{ m.company_name || 'INDEPENDENT' }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
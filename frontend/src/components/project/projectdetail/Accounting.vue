<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['project', 'allCompanies']);

const dbCOAs = ref<any[]>([]);
const activeAccEntity = ref('all');
const searchCOA = ref('');

// Fitur pencarian tetap dipertahankan karena berguna untuk Read-Only
const filteredCOAs = computed(() => {
  let data = [...dbCOAs.value];
  if (searchCOA.value) {
    data = data.filter(c => c.name.toLowerCase().includes(searchCOA.value.toLowerCase()) || c.code.toLowerCase().includes(searchCOA.value.toLowerCase()));
  }
  return data;
});

const fetchMasterFinance = async () => {
  try {
    // Idealnya backend bisa menerima parameter ?project_id= agar COA yang tampil hanya milik project ini
    const res = await api.get('/accounting/coas'); 
    dbCOAs.value = res.data.data || res.data;
  } catch (e) { console.error(e); }
};

onMounted(fetchMasterFinance);
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-in fade-in duration-500 relative">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-2">Business Entities</h3>
        <div class="space-y-2">
          <button @click="activeAccEntity = 'all'" class="w-full flex items-center gap-4 px-5 py-3 rounded-2xl transition-all text-left bg-indigo-50 border border-indigo-100 text-indigo-600">
            <span class="text-[11px] font-black uppercase">Consolidated (All)</span>
          </button>
        </div>
      </div>
    </div>

    <div class="lg:col-span-9 flex flex-col gap-6 min-h-[500px]">
      
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full">
          <i class="fas fa-search absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input v-model="searchCOA" type="text" placeholder="CARI KODE ATAU NAMA AKUN COA..." class="w-full pl-14 pr-6 py-4 bg-slate-50 border border-slate-100 rounded-2xl text-[11px] font-black outline-none uppercase text-slate-700 focus:ring-2 ring-indigo-100 transition-all">
        </div>
        <span class="bg-slate-100 text-slate-500 px-6 py-4 rounded-2xl text-[10px] font-black uppercase whitespace-nowrap flex items-center gap-2">
          <i class="fas fa-lock"></i> Read Only
        </span>
      </div>

      <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm flex-1 overflow-hidden p-2">
        <table class="w-full text-left">
          <thead class="bg-slate-50 text-[9px] font-black text-slate-400 uppercase">
            <tr>
              <th class="p-6 rounded-tl-2xl">Code & Name</th>
              <th class="p-6 rounded-tr-2xl">Category</th>
              </tr>
          </thead>
          <tbody>
            <tr v-if="filteredCOAs.length === 0">
              <td colspan="2" class="p-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Data COA tidak ditemukan</td>
            </tr>
            <tr v-for="coa in filteredCOAs" :key="coa.id" class="border-t border-slate-50 hover:bg-slate-50 transition-colors">
              <td class="p-6">
                <span class="bg-slate-800 text-white px-2 py-1 rounded-md text-[10px] font-black tracking-widest mr-3">{{ coa.code }}</span>
                <span class="font-black text-xs text-slate-700 uppercase">{{ coa.name }}</span>
              </td>
              <td class="p-6 text-[10px] font-black uppercase text-slate-500">{{ coa.category }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>
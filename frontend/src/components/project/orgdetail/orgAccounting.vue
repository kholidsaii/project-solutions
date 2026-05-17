<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['companyData']);
const orgCOAs = ref<any[]>([]);

onMounted(async () => {
  try {
    const res = await api.get('/accounting/coas');
    const allCoas = res.data.data || res.data;
    // Filter akun yang memuat nama PT
    orgCOAs.value = allCoas.filter((c:any) => 
      c.pt_id === props.companyData.id || 
      (c.name && c.name.toLowerCase().includes(props.companyData.name?.toLowerCase()))
    );
  } catch (e) { console.error(e); }
});
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <div class="flex justify-between items-center bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
      <div class="flex items-center gap-4">
         <div class="w-12 h-12 bg-slate-800 rounded-2xl text-white flex items-center justify-center shadow-md"><i class="fas fa-book"></i></div>
         <div>
            <h3 class="text-sm font-black text-slate-800 uppercase">Linked COA (Buku Besar)</h3>
            <p class="text-[10px] text-slate-500 font-bold uppercase mt-0.5">Akun Akuntansi yang membawa nama PT ini</p>
         </div>
      </div>
      <span class="bg-slate-100 text-slate-500 px-6 py-3 rounded-xl text-[10px] font-black uppercase flex items-center gap-2"><i class="fas fa-lock"></i> Read Only</span>
    </div>

    <div class="bg-white rounded-[3rem] border border-slate-200 shadow-sm overflow-hidden p-2">
      <table class="w-full text-left">
        <thead class="bg-slate-50 text-[9px] font-black text-slate-400 uppercase">
          <tr><th class="p-6 rounded-tl-2xl">Code & Name</th><th class="p-6 rounded-tr-2xl">Category</th></tr>
        </thead>
        <tbody>
          <tr v-if="orgCOAs.length === 0">
            <td colspan="2" class="p-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Tidak ada akun COA / piutang terhubung.</td>
          </tr>
          <tr v-for="coa in orgCOAs" :key="coa.id" class="border-t border-slate-50 hover:bg-slate-50">
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
</template>
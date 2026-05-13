<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['project']);
const dbBanks = ref<any[]>([]);
const isLoading = ref(true);

const fetchBanks = async () => {
  try {
    const res = await api.get('/finance/banks');
    dbBanks.value = res.data.data || res.data;
  } catch (error) {
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

// ==========================================
// LOGIKA FILTER BANK BERDASARKAN PROJECT
// ==========================================
const projectBanks = computed(() => {
  // Ambil ID dari PT/Organization yang terhubung di project ini
  const affiliatedPtIds = props.project?.companies?.map((c: any) => c.id) || [];
  
  // Jika project INDEPENDENT (tidak ada PT yang di-assign) -> Munculkan Rekening Personal
  if (affiliatedPtIds.length === 0) {
    return dbBanks.value.filter(b => !b.pt_id);
  }

  // Jika ada PT yang terhubung -> Munculkan hanya Rekening dari PT tersebut
  return dbBanks.value.filter(b => affiliatedPtIds.includes(b.pt_id));
});

// Helper untuk Copy Text
const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text);
  alert(`Nomor Rekening ${text} berhasil di-copy!`);
};

onMounted(fetchBanks);
</script>

<template>
  <div class="animate-in fade-in duration-500 space-y-6">
    
    <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center">
          <i class="fas fa-university"></i>
        </div>
        <div>
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Payment Gateways</h3>
          <p class="text-[10px] text-slate-500 font-bold uppercase mt-0.5">Rekening terdaftar untuk project ini</p>
        </div>
      </div>
      <span class="bg-slate-100 text-slate-500 px-4 py-2 rounded-xl text-[10px] font-black uppercase whitespace-nowrap flex items-center gap-2">
        <i class="fas fa-lock"></i> Read Only
      </span>
    </div>

    <div v-if="isLoading" class="p-12 text-center">
      <i class="fas fa-spinner fa-spin text-3xl text-slate-300"></i>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      
      <div v-for="(bank, index) in projectBanks" :key="bank.id" class="bg-white border border-slate-200 rounded-[2rem] p-6 relative overflow-hidden group shadow-sm hover:shadow-md transition-all">
        
        <div class="absolute left-0 top-0 bottom-0 w-2" :class="['bg-cyan-400', 'bg-emerald-500', 'bg-blue-600', 'bg-indigo-500'][index % 4]"></div>
        
        <div class="flex items-start justify-between ml-2">
          <div class="w-12 h-12 rounded-full border border-slate-200 flex items-center justify-center overflow-hidden bg-slate-50 text-slate-400">
             <i class="fas fa-university text-xl"></i>
          </div>
          
          <div class="flex flex-col items-end">
            <span v-if="bank.pt_id" class="px-3 py-1 rounded-full text-[9px] font-black text-white bg-blue-700 uppercase tracking-widest shadow-sm"><i class="fas fa-building mr-1"></i> Corporate</span>
            <span v-else class="px-3 py-1 rounded-full text-[9px] font-black text-slate-700 bg-yellow-300 uppercase tracking-widest shadow-sm"><i class="fas fa-user-tie mr-1"></i> Personal</span>
            <span class="text-[10px] font-black text-slate-500 uppercase mt-2 bg-slate-100 px-2 py-0.5 rounded">{{ bank.bank_name }}</span>
          </div>
        </div>

        <div class="mt-6 ml-2 space-y-1">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nomor Rekening / Virtual Account</p>
          <div class="flex items-center gap-3">
            <h4 class="text-xl font-black text-slate-800 tracking-wider">{{ bank.account_number }}</h4>
            <button @click="copyToClipboard(bank.account_number)" class="text-slate-300 hover:text-indigo-600 transition-colors" title="Copy Number">
              <i class="far fa-copy text-lg"></i>
            </button>
          </div>
          <div class="pt-4 mt-4 border-t border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">A/N Pemilik (Account Name)</p>
            <p class="text-xs font-black text-slate-700 uppercase">{{ bank.account_name }}</p>
            <p class="text-[9px] text-slate-400 uppercase mt-1">Cabang: {{ bank.branch_office || 'Kantor Pusat' }}</p>
          </div>
        </div>
      </div>

      <div v-if="projectBanks.length === 0" class="col-span-full py-20 text-center bg-white border-2 border-dashed border-slate-200 rounded-[2rem]">
         <i class="fas fa-university text-4xl text-slate-200 mb-4"></i>
         <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Belum Ada Rekening Terhubung<br><span class="font-normal lowercase">Silakan assign PT di tab Teamwork, atau tambahkan rekening bank di master data.</span></p>
      </div>

    </div>

  </div>
</template>
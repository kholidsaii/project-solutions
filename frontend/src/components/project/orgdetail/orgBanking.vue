<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['companyData']);
const dbBanks = ref<any[]>([]);

onMounted(async () => {
  try {
    const res = await api.get('/finance/banks');
    dbBanks.value = res.data.data || res.data;
  } catch (error) { console.error(error); }
});

// Menyaring rekening korporat
const orgBanks = computed(() => {
  return dbBanks.value.filter(b => b.pt_id === props.companyData.id);
});

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text);
  alert(`Nomor Rekening ${text} berhasil di-copy!`);
};
</script>

<template>
  <div class="animate-in fade-in duration-500 space-y-6">
    <div class="flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center"><i class="fas fa-university"></i></div>
        <div>
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Corporate Bank Accounts</h3>
          <p class="text-[10px] text-slate-500 font-bold uppercase mt-0.5">Informasi Rekening Perusahaan / PT</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="bank in orgBanks" :key="bank.id" class="bg-white border border-slate-200 rounded-[2rem] p-6 relative overflow-hidden shadow-sm">
        <div class="absolute left-0 top-0 bottom-0 w-2 bg-blue-700"></div>
        <div class="flex items-start justify-between ml-2">
          <div class="w-12 h-12 rounded-full border border-slate-200 flex items-center justify-center bg-slate-50 text-slate-400"><i class="fas fa-university text-xl"></i></div>
          <span class="px-3 py-1 rounded-full text-[9px] font-black text-white bg-blue-700 uppercase shadow-sm"><i class="fas fa-building mr-1"></i> Corporate</span>
        </div>
        <div class="mt-6 ml-2 space-y-1">
          <h4 class="text-[10px] font-black text-slate-400 uppercase mt-2">{{ bank.bank_name }}</h4>
          <div class="flex items-center gap-3">
            <h4 class="text-xl font-black text-slate-800 tracking-wider">{{ bank.account_number }}</h4>
            <button @click="copyToClipboard(bank.account_number)" class="text-slate-300 hover:text-indigo-600"><i class="far fa-copy text-lg"></i></button>
          </div>
          <div class="pt-4 mt-4 border-t border-slate-100">
            <p class="text-[10px] font-bold text-slate-400 uppercase">A/N Pemilik</p>
            <p class="text-xs font-black text-slate-700 uppercase">{{ bank.account_name }}</p>
          </div>
        </div>
      </div>

      <div v-if="orgBanks.length === 0" class="col-span-full py-20 text-center bg-white border-2 border-dashed border-slate-200 rounded-[2rem]">
         <i class="fas fa-university text-4xl text-slate-200 mb-4"></i>
         <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Belum Ada Rekening Terhubung</p>
      </div>
    </div>
  </div>
</template>
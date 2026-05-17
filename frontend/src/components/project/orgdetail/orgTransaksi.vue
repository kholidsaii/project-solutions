<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../../api/axios';

const props = defineProps(['companyData']);
const orgTransactions = ref<any[]>([]);

const formatCurrency = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);

onMounted(async () => {
  try {
    const res = await api.get('/finance/transactions');
    const allTrx = res.data.data || res.data;
    // Filter transaksi yang membawa pt_id atau deskripsinya memuat nama PT
    orgTransactions.value = allTrx.filter((t:any) => 
      t.pt_id === props.companyData.id || 
      (t.description && t.description.toLowerCase().includes(props.companyData.name?.toLowerCase()))
    );
  } catch (e) { console.error(e); }
});
</script>

<template>
  <div class="space-y-6 animate-in fade-in duration-500">
    <div class="flex justify-between items-center bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-500 rounded-2xl text-white flex items-center justify-center shadow-md"><i class="fas fa-exchange-alt"></i></div>
        <div>
          <h3 class="text-sm font-black text-slate-800 uppercase">Corporate Cashbook</h3>
          <p class="text-[10px] text-slate-500 font-bold uppercase mt-0.5">Riwayat Transaksi, Invoicing & Kasbon PT</p>
        </div>
      </div>
      <span class="bg-slate-100 text-slate-500 px-6 py-3 rounded-xl text-[10px] font-black uppercase flex items-center gap-2"><i class="fas fa-lock"></i> Read Only</span>
    </div>

    <table class="w-full text-left bg-white border border-slate-100 rounded-[2rem] overflow-hidden shadow-sm">
      <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400 border-b border-slate-100">
        <tr><th class="p-6">Ref No. & Deskripsi</th><th class="p-6 text-center">Tanggal</th><th class="p-6 text-right">Amount</th></tr>
      </thead>
      <tbody>
        <tr v-if="orgTransactions.length === 0">
          <td colspan="3" class="p-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Tidak ada riwayat transaksi</td>
        </tr>
        <tr v-for="trx in orgTransactions" :key="trx.id" class="border-b border-slate-50 text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors">
          <td class="p-6">
            <span class="block text-[9px] font-black text-slate-400 uppercase mb-1">{{ trx.transaction_number }}</span>
            <span class="uppercase text-slate-700">{{ trx.description }}</span>
          </td>
          <td class="p-6 text-center text-[10px]">{{ trx.date }}</td>
          <td class="p-6 text-right font-black" :class="trx.type === 'inflow' ? 'text-emerald-500' : 'text-rose-500'">
            <i class="fas text-[10px] mr-1" :class="trx.type === 'inflow' ? 'fa-arrow-down' : 'fa-arrow-up'"></i>
            {{ formatCurrency(trx.amount) }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
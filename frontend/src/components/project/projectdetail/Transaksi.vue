<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../../api/axios';

const props = defineProps(['project']);
const route = useRoute();

const activeFinanceNav = ref('overview');
const projectTransactions = ref<any[]>([]);

const formatCurrency = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);

// Kalkulasi summary pengeluaran
const calculateTotalExpenses = () => (props.project?.work_orders?.reduce((t:any, w:any) => t + parseFloat(w.budget), 0) || 0) + (props.project?.purchasings?.reduce((t:any, p:any) => t + parseFloat(p.total_price), 0) || 0);

const fetchProjectTransactions = async () => {
  try {
    // Memanggil API dengan parameter project spesifik agar datanya terfilter
    const res = await api.get('/finance/transactions', { params: { project_id: route.params.id, status: 'all', type: 'all' } });
    projectTransactions.value = res.data;
  } catch (e) { console.error(e); }
};

onMounted(async () => {
  await fetchProjectTransactions();
});
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-in fade-in zoom-in-95 duration-500 relative">
    
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200/60 sticky top-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 ml-2">Finance Modules</h3>
        <div class="space-y-3">
          <button @click="activeFinanceNav = 'overview'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" :class="activeFinanceNav === 'overview' ? 'bg-slate-900 text-white shadow-md' : 'hover:bg-slate-50 text-slate-500'">
            <div class="flex items-center gap-4"><i class="fas fa-chart-pie"></i><span class="text-[11px] font-black uppercase tracking-tight">Analytics</span></div>
          </button>
          <button @click="activeFinanceNav = 'transaksi'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" :class="activeFinanceNav === 'transaksi' ? 'bg-slate-900 text-white shadow-md' : 'hover:bg-slate-50 text-slate-500'">
            <div class="flex items-center gap-4"><i class="fas fa-exchange-alt"></i><span class="text-[11px] font-black uppercase tracking-tight">Cashbook</span></div>
          </button>
          <button @click="activeFinanceNav = 'invoicing'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" :class="activeFinanceNav === 'invoicing' ? 'bg-slate-900 text-white shadow-md' : 'hover:bg-slate-50 text-slate-500'">
            <div class="flex items-center gap-4"><i class="fas fa-file-invoice-dollar"></i><span class="text-[11px] font-black uppercase tracking-tight">Invoicing</span></div>
          </button>
        </div>
      </div>
    </div>

    <div class="lg:col-span-9 space-y-8">
      
      <div v-if="activeFinanceNav === 'overview'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white border border-slate-100 p-6 rounded-[2.5rem] shadow-sm">
          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Gross Revenue</p>
          <h3 class="text-xl font-black text-slate-800">{{ formatCurrency(project?.contract_value) }}</h3>
        </div>
        <div class="bg-white border border-slate-100 p-6 rounded-[2.5rem] shadow-sm">
          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Burn Rate</p>
          <h3 class="text-xl font-black text-rose-600">{{ formatCurrency(calculateTotalExpenses()) }}</h3>
        </div>
        <div class="p-6 rounded-[2.5rem] shadow-xl text-white bg-gradient-to-br from-emerald-500 to-teal-600">
          <p class="text-[9px] font-black opacity-80 uppercase tracking-widest mb-2">Net Profit</p>
          <h3 class="text-xl font-black">{{ formatCurrency((project?.contract_value || 0) - calculateTotalExpenses()) }}</h3>
        </div>
      </div>

      <div v-if="activeFinanceNav === 'transaksi'" class="space-y-6">
        <div class="flex justify-between items-center bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-500 rounded-2xl text-white flex items-center justify-center shadow-md"><i class="fas fa-exchange-alt"></i></div>
            <h3 class="text-sm font-black text-slate-800 uppercase">Cashbook Ledger</h3>
          </div>
          <span class="bg-slate-100 text-slate-500 px-6 py-3 rounded-xl text-[10px] font-black uppercase whitespace-nowrap flex items-center gap-2">
            <i class="fas fa-lock"></i> Read Only
          </span>
        </div>

        <table class="w-full text-left bg-white border border-slate-100 rounded-[2rem] overflow-hidden shadow-sm">
          <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400 border-b border-slate-100">
            <tr><th class="p-6">Ref No.</th><th class="p-6">Details / Description</th><th class="p-6 text-right">Amount</th></tr>
          </thead>
          <tbody>
            <tr v-if="projectTransactions.length === 0">
              <td colspan="3" class="p-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Belum ada transaksi</td>
            </tr>
            <tr v-for="trx in projectTransactions" :key="trx.id" class="border-b border-slate-50 text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors">
              <td class="p-6 uppercase text-slate-400">{{ trx.transaction_number }}</td>
              <td class="p-6 uppercase text-slate-700">{{ trx.description }}</td>
              <td class="p-6 text-right font-black" :class="trx.type === 'inflow' ? 'text-emerald-500' : 'text-rose-500'">
                <i class="fas text-[10px] mr-1" :class="trx.type === 'inflow' ? 'fa-arrow-down' : 'fa-arrow-up'"></i>
                {{ formatCurrency(trx.amount) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="activeFinanceNav === 'invoicing'" class="space-y-6">
        <div class="flex justify-between items-center bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-600 rounded-2xl text-white flex items-center justify-center shadow-md"><i class="fas fa-file-invoice-dollar"></i></div>
            <h3 class="text-sm font-black text-slate-800 uppercase">Project Invoices</h3>
          </div>
        </div>

        <table class="w-full text-left bg-white border border-slate-100 rounded-[2rem] overflow-hidden shadow-sm">
          <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400 border-b border-slate-100">
            <tr><th class="p-6">Title / Keterangan</th><th class="p-6 text-right">Amount</th><th class="p-6 text-center">Status</th></tr>
          </thead>
          <tbody>
            <tr v-if="!project?.invoices || project.invoices.length === 0">
              <td colspan="3" class="p-10 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">Belum ada tagihan (Invoice)</td>
            </tr>
            <tr v-for="inv in project?.invoices" :key="inv.id" class="border-b border-slate-50 text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors">
              <td class="p-6 uppercase text-slate-700">{{ inv.title }}</td>
              <td class="p-6 text-right font-black text-slate-800">{{ formatCurrency(inv.amount) }}</td>
              <td class="p-6 text-center">
                <span class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest" :class="inv.status === 'Paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                  {{ inv.status }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>
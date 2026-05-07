<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../../api/axios';
const props = defineProps(['project']);
const emit = defineEmits(['refresh']);
const route = useRoute();

const activeFinanceNav = ref('overview');
const showTxModal = ref(false);
const projectTransactions = ref<any[]>([]);
const dbCOAs = ref<any[]>([]);
const dbBanks = ref<any[]>([]);

const txForm = ref({ type: 'outflow', date: new Date().toISOString().split('T')[0], ref_number: '', coa_id: '', method: 'transfer', bank_from: '', bank_to: '', amount: 0, description: '', attachment: null as File | null });
const invForm = ref({ title: '', amount: 0, due_date: '' });

const formatCurrency = (val: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(val || 0);
const calculateTotalExpenses = () => (props.project?.work_orders?.reduce((t:any, w:any) => t + parseFloat(w.budget), 0) || 0) + (props.project?.purchasings?.reduce((t:any, p:any) => t + parseFloat(p.total_price), 0) || 0);

const availableCOAs = computed(() => dbCOAs.value.filter(c => !c.pt_id || c.pt_id === props.project?.company_id));

const fetchMasterFinance = async () => {
  try {
    const resCOA = await api.get('/accounting/coas');
    dbCOAs.value = resCOA.data.data || resCOA.data;
  } catch (e) { console.error(e); }
};

const fetchProjectTransactions = async () => {
  try {
    const res = await api.get('/finance/transactions', { params: { project_id: route.params.id, status: 'all', type: 'all' } });
    projectTransactions.value = res.data;
  } catch (e) { console.error(e); }
};

const handleSaveTransaction = async () => {
  if (txForm.value.amount <= 0 || !txForm.value.coa_id) return;
  try {
    const fd = new FormData();
    Object.keys(txForm.value).forEach(key => fd.append(key, (txForm.value as any)[key]));
    fd.append('project_id', route.params.id as string);
    await api.post('/finance/transactions', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    showTxModal.value = false;
    await fetchProjectTransactions();
  } catch (e) { console.error(e); }
};

const handleSaveInvoice = async () => {
  if (!invForm.value.title || invForm.value.amount <= 0) return;
  try {
    await api.post('/project-invoices', { project_id: route.params.id, ...invForm.value });
    invForm.value = { title: '', amount: 0, due_date: '' };
    emit('refresh');
  } catch (e) { console.error(e); }
};

const updateInvStatus = async (id: number, status: string) => {
  try {
    await api.put(`/project-invoices/${id}/status`, { status });
    emit('refresh');
  } catch (e) { console.error(e); }
};

onMounted(async () => {
  await fetchMasterFinance();
  await fetchProjectTransactions();
});
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-in fade-in zoom-in-95 duration-500 relative">
    <div class="lg:col-span-3 space-y-6">
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm border border-slate-200/60 sticky top-4">
        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 ml-2">Finance Modules</h3>
        <div class="space-y-3">
          <button @click="activeFinanceNav = 'overview'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" :class="activeFinanceNav === 'overview' ? 'bg-slate-900 text-white' : 'hover:bg-slate-50 text-slate-500'">
            <div class="flex items-center gap-4"><i class="fas fa-chart-pie"></i><span class="text-[11px] font-black uppercase tracking-tight">Analytics</span></div>
          </button>
          <button @click="activeFinanceNav = 'transaksi'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" :class="activeFinanceNav === 'transaksi' ? 'bg-slate-900 text-white' : 'hover:bg-slate-50 text-slate-500'">
            <div class="flex items-center gap-4"><i class="fas fa-exchange-alt"></i><span class="text-[11px] font-black uppercase tracking-tight">Cashbook</span></div>
          </button>
          <button @click="activeFinanceNav = 'invoicing'" class="w-full flex items-center justify-between px-5 py-4 rounded-2xl transition-all group" :class="activeFinanceNav === 'invoicing' ? 'bg-slate-900 text-white' : 'hover:bg-slate-50 text-slate-500'">
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
          <div class="flex items-center gap-4"><div class="w-12 h-12 bg-emerald-500 rounded-2xl text-white flex items-center justify-center"><i class="fas fa-exchange-alt"></i></div><h3 class="text-sm font-black text-slate-800 uppercase">Cashbook Ledger</h3></div>
          <button @click="showTxModal = true" class="bg-slate-900 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest">Record Transaction</button>
        </div>
        <table class="w-full text-left bg-white border rounded-[2rem] overflow-hidden">
          <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400">
            <tr><th class="p-6">Ref</th><th class="p-6">Details</th><th class="p-6 text-right">Amount</th></tr>
          </thead>
          <tbody>
            <tr v-for="trx in projectTransactions" :key="trx.id" class="border-t border-slate-50 text-[11px] font-bold text-slate-600">
              <td class="p-6 uppercase">{{ trx.transaction_number }}</td>
              <td class="p-6 uppercase">{{ trx.description }}</td>
              <td class="p-6 text-right font-black" :class="trx.type === 'inflow' ? 'text-emerald-500' : 'text-rose-500'">{{ formatCurrency(trx.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="activeFinanceNav === 'invoicing'" class="space-y-6">
        <div class="bg-white p-8 rounded-[3rem] border border-slate-200 shadow-sm space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <input v-model="invForm.title" placeholder="INVOICE TITLE" class="bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase">
            <input v-model="invForm.amount" type="number" placeholder="AMOUNT" class="bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none uppercase">
            <input v-model="invForm.due_date" type="date" class="bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold outline-none">
          </div>
          <button @click="handleSaveInvoice" class="w-full bg-slate-900 text-white py-5 rounded-2xl text-[10px] font-black uppercase">Generate Invoice</button>
        </div>
        <table class="w-full text-left bg-white border rounded-[2rem] overflow-hidden">
          <thead class="bg-slate-50 text-[9px] font-black uppercase text-slate-400">
            <tr><th class="p-6">Title</th><th class="p-6 text-right">Amount</th><th class="p-6 text-center">Status</th></tr>
          </thead>
          <tbody>
            <tr v-for="inv in project?.invoices" :key="inv.id" class="border-t border-slate-50 text-[11px] font-bold text-slate-600">
              <td class="p-6 uppercase">{{ inv.title }}</td>
              <td class="p-6 text-right font-black">{{ formatCurrency(inv.amount) }}</td>
              <td class="p-6 text-center"><button @click="updateInvStatus(inv.id, 'Paid')" v-if="inv.status !== 'Paid'" class="text-emerald-500">Mark Paid</button><span v-else class="text-slate-400">Settled</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="showTxModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white rounded-[3rem] w-full max-w-4xl p-8 space-y-8 animate-in zoom-in-95">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-black text-slate-800 uppercase italic">Catat Transaksi</h3>
          <button @click="showTxModal = false" class="text-slate-400"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="grid grid-cols-2 gap-8">
          <select v-model="txForm.type" class="w-full bg-slate-50 border rounded-2xl px-5 py-4 text-xs font-bold outline-none"><option value="inflow">Inflow</option><option value="outflow">Outflow</option></select>
          <select v-model="txForm.coa_id" class="w-full bg-slate-50 border rounded-2xl px-5 py-4 text-xs font-bold uppercase outline-none">
            <option value="" disabled>-- Pilih COA --</option>
            <option v-for="coa in availableCOAs" :key="coa.id" :value="coa.id">[{{ coa.code }}] {{ coa.name }}</option>
          </select>
          <input v-model="txForm.amount" type="number" placeholder="Nominal" class="w-full bg-slate-50 border rounded-2xl px-5 py-4 text-xs font-bold outline-none">
          <textarea v-model="txForm.description" placeholder="Deskripsi..." class="w-full bg-slate-50 border rounded-2xl px-5 py-4 text-xs font-bold outline-none"></textarea>
        </div>
        <button @click="handleSaveTransaction" class="w-full bg-indigo-600 text-white py-5 rounded-2xl text-[10px] font-black uppercase">Simpan Transaksi</button>
      </div>
    </div>
  </div>
</template>


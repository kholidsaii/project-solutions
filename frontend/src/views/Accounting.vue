<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import api from '../api/axios';

// ==========================================
// STATE MANAGEMENT
// ==========================================
const currentTab = ref('ledger');
const selectedPT = ref('all');
const isLoading = ref(false);

const journals = ref<any[]>([]);
const coas = ref<any[]>([]);
const companies = ref<any[]>([]);

// ==========================================
// DATA FETCHING
// ==========================================
const fetchData = async () => {
  isLoading.value = true;
  try {
    const [resJurnal, resCoa, resComp] = await Promise.all([
      api.get('/accounting/ledger', { params: { pt_id: selectedPT.value } }),
      api.get('/accounting/coas', { params: { pt_id: selectedPT.value } }),
      api.get('/companies')
    ]);
    journals.value = resJurnal.data;
    coas.value = resCoa.data;
    companies.value = resComp.data;
  } catch (e) {
    console.error("Accounting Sync Failed", e);
  } finally {
    isLoading.value = false;
  }
};

// ==========================================
// CALCULATED ANALYTICS (Perfect Integration)
// ==========================================
const totalDebit = computed(() => journals.value.reduce((t, j) => t + (parseFloat(j.debit) || 0), 0));
const totalCredit = computed(() => journals.value.reduce((t, j) => t + (parseFloat(j.credit) || 0), 0));
const netBalance = computed(() => totalDebit.value - totalCredit.value);

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

watch(selectedPT, fetchData);
onMounted(fetchData);
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8 space-y-6">
      
      <!-- HEADER SECTION (Branded like Works/Financial) -->
      <div class="bg-white border border-slate-200 rounded-[2.5rem] p-8 shadow-sm relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-64 h-64 bg-emerald-50 rounded-full -mr-32 -mt-32 transition-transform group-hover:scale-110 duration-1000"></div>
        
        <div class="flex flex-col md:flex-row justify-between items-center gap-6 relative z-10">
          <div class="flex items-center gap-6">
            <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center shadow-2xl shadow-indigo-100 transform -rotate-3 group-hover:rotate-0 transition-transform">
              <i class="fas fa-file-invoice-dollar text-3xl text-emerald-400"></i>
            </div>
            <div>
              <h1 class="text-2xl font-black text-slate-800 uppercase italic tracking-tighter leading-none">Accounting Ledger</h1>
              <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">Consolidated Audit Trail</p>
            </div>
          </div>

          <div class="flex items-center gap-3 bg-white border border-slate-100 p-3 rounded-2xl shadow-inner">
            <span class="text-[9px] font-black text-slate-400 uppercase ml-2">Audit Focus:</span>
            <select v-model="selectedPT" class="bg-slate-50 border-none rounded-xl px-4 py-2 text-[11px] font-black text-indigo-600 uppercase outline-none min-w-[220px]">
              <option value="all">ALL ENTITIES (CONSOLIDATED)</option>
              <option v-for="cp in companies" :key="cp.id" :value="cp.id">{{ cp.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ANALYTICS CARDS (The Stats) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute inset-0 bg-emerald-500 translate-y-full group-hover:translate-y-0 transition-transform duration-500 opacity-[0.03]"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Accumulated Inflow (Debit)</p>
            <h4 class="text-2xl font-black text-emerald-600 italic">+ {{ formatCurrency(totalDebit) }}</h4>
        </div>
        <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm relative overflow-hidden group">
            <div class="absolute inset-0 bg-rose-500 translate-y-full group-hover:translate-y-0 transition-transform duration-500 opacity-[0.03]"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Accumulated Outflow (Credit)</p>
            <h4 class="text-2xl font-black text-rose-500 italic">- {{ formatCurrency(totalCredit) }}</h4>
        </div>
        <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fas fa-balance-scale text-5xl text-white"></i>
            </div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Ledger Net Position</p>
            <h4 class="text-2xl font-black text-white italic" :class="netBalance >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                {{ formatCurrency(netBalance) }}
            </h4>
        </div>
      </div>

      <!-- TABS NAVIGATION -->
      <div class="bg-slate-200/50 p-1.5 rounded-2xl inline-flex gap-2">
        <button v-for="tab in ['ledger', 'coa']" :key="tab"
          @click="currentTab = tab"
          class="px-8 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
          :class="currentTab === tab ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
          {{ tab === 'ledger' ? 'Buku Besar' : 'Chart of Accounts' }}
        </button>
      </div>

      <!-- LEDGER VIEW -->
      <div v-if="currentTab === 'ledger'" class="animate-in fade-in slide-in-from-bottom-4 duration-700 pb-10">
        <div class="bg-white border border-slate-200 rounded-[3rem] overflow-hidden shadow-sm">
          <div class="px-10 py-6 bg-slate-50/50 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Transaction Records</h3>
            <span class="text-[9px] font-bold text-slate-400 uppercase italic">Showing {{ journals.length }} Transactions</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                <tr class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                    <th class="p-8">Date & Project</th>
                    <th class="p-8">Description</th>
                    <th class="p-8">Account Classification</th>
                    <th class="p-8 text-right">Debit</th>
                    <th class="p-8 text-right">Credit</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                <tr v-for="j in journals" :key="j.id" class="hover:bg-slate-50/80 transition-all group">
                    <td class="p-8">
                    <p class="text-[11px] font-black text-slate-400">{{ j.transaction_date }}</p>
                    <p class="text-[9px] font-black text-indigo-500 uppercase mt-1">{{ j.project_title || 'GENERAL OPERATION' }}</p>
                    </td>
                    <td class="p-8">
                    <p class="text-[11px] font-black text-slate-800 uppercase leading-relaxed">{{ j.description }}</p>
                    <p class="text-[8px] font-bold text-slate-300 mt-1">REF: #{{ j.reference_type }}-{{ j.id }}</p>
                    </td>
                    <td class="p-8">
                    <span class="px-3 py-1 bg-slate-100 rounded-lg text-[9px] font-black text-slate-500 border border-slate-200 uppercase group-hover:bg-white transition-all">
                        {{ j.coa_code }} • {{ j.coa_name }}
                    </span>
                    </td>
                    <td class="p-8 text-right text-emerald-600 font-black italic">
                    {{ j.debit > 0 ? formatCurrency(j.debit) : '-' }}
                    </td>
                    <td class="p-8 text-right text-rose-500 font-black italic">
                    {{ j.credit > 0 ? formatCurrency(j.credit) : '-' }}
                    </td>
                </tr>
                </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- COA VIEW -->
      <div v-if="currentTab === 'coa'" class="animate-in fade-in duration-500 pb-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="cat in ['Asset', 'Revenue', 'Expense']" :key="cat" class="bg-white border border-slate-200 rounded-[2.5rem] p-10 shadow-sm">
            <div class="flex justify-between items-center mb-8 border-b border-slate-50 pb-4">
                <h4 class="text-xs font-black uppercase text-indigo-900 tracking-[0.2em]">{{ cat }} Accounts</h4>
                <div class="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center text-[10px] text-slate-300">
                    <i class="fas fa-plus"></i>
                </div>
            </div>
            <div class="space-y-4">
              <div v-for="coa in coas.filter(c => c.category === cat)" :key="coa.id" 
                class="group p-4 bg-slate-50/50 rounded-2xl hover:bg-indigo-600 hover:shadow-lg hover:shadow-indigo-100 transition-all cursor-pointer border border-slate-100 hover:border-indigo-600">
                <div class="flex justify-between items-start">
                  <div>
                    <p class="text-[11px] font-black text-slate-800 uppercase group-hover:text-white">{{ coa.name }}</p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5 group-hover:text-indigo-200">CODE: {{ coa.code }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
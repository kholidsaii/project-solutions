<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../api/axios';

const currentTab = ref('ledger'); // ledger | coa | reports
const selectedPT = ref('all');

const journalEntries = ref<any[]>([]);
const coaList = ref<any[]>([]);

// Agregasi Data Jurnal
const totalDebit = computed(() => journalEntries.value.reduce((t: any, j: any) => t + parseFloat(j.debit || 0), 0));
const totalCredit = computed(() => journalEntries.value.reduce((t: any, j: any) => t + parseFloat(j.credit || 0), 0));

const fetchAccountingData = async () => {
  try {
    const [resJournal, resCoa] = await Promise.all([
      api.get('/accounting/journals', { params: { pt_id: selectedPT.value } }),
      api.get('/accounting/coas', { params: { pt_id: selectedPT.value } })
    ]);
    journalEntries.value = resJournal.data;
    coaList.value = resCoa.data;
  } catch (e) {
    console.error("Gagal load data accounting", e);
  }
};

const formatCurrency = (val: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

onMounted(fetchAccountingData);
</script>
<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8">
      
      <!-- HEADER ACCOUNTING -->
      <div class="bg-white border-x border-t border-slate-200 rounded-t-3xl pt-8 pb-4 shadow-sm">
        <div class="flex items-center justify-between px-8">
          <div class="flex items-center gap-6">
            <div class="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center shadow-lg">
              <i class="fas fa-book text-3xl text-white"></i>
            </div>
            <div>
              <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Accounting & Ledger</h1>
              <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">General Ledger & COA Management</p>
            </div>
          </div>
          <div class="flex gap-3">
             <button class="bg-slate-100 text-slate-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-200 hover:bg-white transition-all">
                <i class="fas fa-print mr-2"></i> Print Report
             </button>
             <button class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg active:scale-95 transition-all">
                <i class="fas fa-plus mr-2"></i> Manual Journal
             </button>
          </div>
        </div>
      </div>

      <!-- TAB NAVIGATION -->
      <div class="bg-[#F1F5F9] border-x border-y border-slate-200 py-3 px-8 flex gap-8">
        <button v-for="t in ['ledger', 'coa', 'reports']" :key="t"
          @click="currentTab = t" 
          class="text-[10px] font-black uppercase tracking-widest transition-all"
          :class="currentTab === t ? 'text-indigo-600' : 'text-slate-400'">
          {{ t }}
        </button>
      </div>

      <!-- MAIN LEDGER TABLE -->
      <div v-if="currentTab === 'ledger'" class="py-8 animate-in fade-in duration-500">
        <div class="bg-white border border-slate-200 rounded-[2rem] overflow-hidden shadow-sm">
          <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                <th class="p-6">Date</th>
                <th class="p-6">Account (COA)</th>
                <th class="p-6">Description / Project</th>
                <th class="p-6">Reference</th>
                <th class="p-6 text-right">Debit</th>
                <th class="p-6 text-right">Credit</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="j in journalEntries" :key="j.id" class="hover:bg-slate-50/50 transition-all text-[11px]">
                <td class="p-6 font-bold text-slate-500">{{ j.transaction_date }}</td>
                <td class="p-6">
                   <div class="font-black text-slate-800 uppercase">{{ j.coa_name }}</div>
                   <div class="text-[9px] text-indigo-500 font-bold tracking-tighter">{{ j.coa_code }}</div>
                </td>
                <td class="p-6 uppercase">
                   <span class="font-bold text-slate-700">{{ j.description }}</span>
                   <p class="text-[9px] text-slate-400">Project ID: #{{ j.project_id }}</p>
                </td>
                <td class="p-6">
                   <span class="px-2 py-1 bg-slate-100 text-slate-500 rounded font-black text-[8px] uppercase border border-slate-200">
                      {{ j.reference_type }}
                   </span>
                </td>
                <td class="p-6 text-right font-black text-emerald-600">{{ j.debit > 0 ? formatCurrency(j.debit) : '-' }}</td>
                <td class="p-6 text-right font-black text-rose-500">{{ j.credit > 0 ? formatCurrency(j.credit) : '-' }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-900 text-white font-black">
               <tr>
                  <td colspan="4" class="p-6 text-[10px] uppercase tracking-widest text-right">Trial Balance Total</td>
                  <td class="p-6 text-right italic">{{ formatCurrency(totalDebit) }}</td>
                  <td class="p-6 text-right italic">{{ formatCurrency(totalCredit) }}</td>
               </tr>
            </tfoot>
          </table>
        </div>

        <!-- INTERCOMPANY SUMMARY (MOCKUP) -->
        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-8">
           <div class="bg-indigo-600 p-8 rounded-[2.5rem] text-white shadow-xl shadow-indigo-100">
              <h3 class="text-[10px] font-black uppercase tracking-widest opacity-70 mb-6">Intercompany Receivable (Piutang Antar PT)</h3>
              <div class="space-y-4">
                 <div class="flex justify-between items-center border-b border-white/10 pb-3">
                    <span class="text-[11px] font-bold uppercase">PT. Solusi Maju → PT. Digital Sinergi</span>
                    <span class="text-sm font-black italic">Rp 45.000.000</span>
                 </div>
                 <p class="text-[8px] opacity-50 uppercase font-bold">*Modal dipinjamkan untuk Project RS Hermina</p>
              </div>
           </div>
           <div class="bg-white border border-slate-200 p-8 rounded-[2.5rem] shadow-sm">
              <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-6">Accounting Status</h3>
              <div class="flex items-center gap-6">
                 <div class="w-20 h-20 rounded-full border-8 border-emerald-500 border-t-slate-100 flex items-center justify-center">
                    <span class="text-xs font-black">100%</span>
                 </div>
                 <div>
                    <p class="text-[11px] font-black text-slate-800 uppercase italic">Balanced Journal</p>
                    <p class="text-[9px] font-bold text-slate-400">Semua entitas PT sudah sinkron.</p>
                 </div>
              </div>
           </div>
        </div>
      </div>

    </div>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue';

// Import komponen-komponen tab dari folder finance
import OverviewTab from '../components/finance/overview.vue';
import TransaksiTab from '../components/finance/transaksi.vue';
import AccountingTab from '../components/finance/accounting.vue';
import BankingTab from '../components/finance/banking.vue';
import SetupTab from '../components/finance/setup.vue';

const mainTabs = ['overview', 'transaksi', 'accounting', 'banking', 'setup'];
const currentMainTab = ref('transaksi');
</script>

<template>
  <div class="min-h-screen bg-[#F1F5F9] pb-20 md:pl-20 font-sans overflow-x-hidden text-slate-900">
    <div class="max-w-7xl mx-auto px-6 mt-8 space-y-6">
      
      <!-- HEADER & MAIN 5 TABS NAVIGATION -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="flex items-center gap-5">
          <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center text-white text-xl shadow-lg transform -rotate-6">
            <i class="fas fa-wallet"></i>
          </div>
          <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tighter uppercase italic">Finance & Accounting</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Enterprise Resource Planning</p>
          </div>
        </div>

        <!-- 5 Main Tabs -->
        <div class="flex gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-100 overflow-x-auto w-full md:w-auto">
          <button v-for="tab in mainTabs" :key="tab" @click="currentMainTab = tab"
            class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap"
            :class="currentMainTab === tab ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/50' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'">
            {{ tab }}
          </button>
        </div>
      </div>

      <!-- AREA RENDER KOMPONEN TAB -->
      <OverviewTab v-if="currentMainTab === 'overview'" />
      <TransaksiTab v-if="currentMainTab === 'transaksi'" />
      <AccountingTab v-if="currentMainTab === 'accounting'" />
      <BankingTab v-if="currentMainTab === 'banking'" />
      <SetupTab v-if="currentMainTab === 'setup'" />

    </div>
  </div>
</template>
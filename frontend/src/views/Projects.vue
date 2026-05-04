<script setup lang="ts">
import { ref } from 'vue';
import OverviewTab from '../components/project/Overview.vue';
import DataProjectTab from '../components/project/DataProject.vue';
import SetupTab from '../components/project/Setup.vue';
import TeamworkTab from '../components/project/Teamwork.vue';
import DocumentsTab from '../components/project/Documents.vue';

const currentTab = ref('overview');
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] pb-20 md:pl-20 font-sans overflow-x-hidden text-slate-900">
    <div class="max-w-6xl mx-auto px-6 mt-8">
      
      <!-- HEADER & TABS NAVIGATION (Gaya Financial.vue) -->
      <div class="bg-white rounded-[2.5rem] p-6 shadow-sm flex flex-col md:flex-row justify-between items-center gap-6">
        
        <!-- Bagian Judul & Icon -->
        <div class="flex items-center gap-5">
          <div class="w-14 h-14 bg-[#EF4444] rounded-2xl flex items-center justify-center text-white text-xl shadow-lg transform -rotate-6">
            <i class="fas fa-check"></i>
          </div>
          <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tighter uppercase italic">Works</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Project Management</p>
          </div>
        </div>

        <!-- 4 Main Tabs -->
        <div class="flex gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-100 overflow-x-auto w-full md:w-auto custom-scroll">
          <button v-for="tab in ['overview', 'data', 'teamwork', 'documents', 'setup']" :key="tab" 
            @click="currentTab = tab"
            class="px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap"
            :class="currentTab === tab ? 'bg-white text-indigo-600 shadow-sm border border-slate-200/50' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'">
            {{ tab }}
          </button>
        </div>

      </div>

      <!-- COMPONENTS RENDER AREA -->
      <div class="mt-2">
        <OverviewTab v-if="currentTab === 'overview'" />
        <DataProjectTab v-if="currentTab === 'data'" />
        <TeamworkTab v-if="currentTab === 'teamwork'" />
        <DocumentsTab v-if="currentTab === 'documents'" />
        <SetupTab v-if="currentTab === 'setup'" />
      </div>

    </div>
  </div>
</template>

<style scoped>
.font-sans { font-family: 'Inter', sans-serif; }
/* Scrollbar khusus untuk tab jika di layar HP / kecil */
.custom-scroll::-webkit-scrollbar { height: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>
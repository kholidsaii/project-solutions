<script setup lang="ts">
const props = defineProps(['companyData']);
</script>

<template>
  <div class="animate-in fade-in duration-500 bg-white p-8 sm:p-12 rounded-[2rem] border border-slate-200 shadow-sm text-center max-w-3xl mx-auto mt-4">
    <div class="w-20 h-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-6">
      <i class="fas fa-building text-4xl"></i>
    </div>
    <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Organization Access & Setup</h2>
    <p class="text-xs font-bold text-slate-500 mt-2 leading-relaxed uppercase">
      Pengaturan lisensi, batasan jumlah pengguna (seat limit), dan keamanan API khusus untuk <span class="text-indigo-600">{{ companyData?.name }}</span> saat ini sedang dalam pengembangan.
    </p>
    
    <div class="mt-8 pt-8 border-t border-slate-100 flex justify-center gap-4">
       <button disabled class="px-6 py-3 bg-slate-100 text-slate-400 text-[10px] font-black uppercase rounded-xl cursor-not-allowed"><i class="fas fa-file-contract mr-2"></i> View Contract</button>
       <button disabled class="px-6 py-3 bg-slate-100 text-slate-400 text-[10px] font-black uppercase rounded-xl cursor-not-allowed"><i class="fas fa-shield-alt mr-2"></i> Manage Permissions</button>
    </div>
  </div>
</template>
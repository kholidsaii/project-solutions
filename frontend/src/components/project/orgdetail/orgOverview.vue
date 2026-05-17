<script setup lang="ts">
import { useRoute } from 'vue-router';
import api from '../../../api/axios';

const props = defineProps(['companyData']);
const emit = defineEmits(['refresh']);
const route = useRoute();

const updateOrgDetail = async () => {
  try {
    await api.put(`/companies/${route.params.id}`, props.companyData);
    emit('refresh');
    alert("Profil Organisasi/PT berhasil diperbarui!");
  } catch (e) {
    console.error("Gagal update profil", e);
    alert("Gagal menyimpan perubahan.");
  }
};
</script>

<template>
  <div class="space-y-8 animate-in fade-in duration-500">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <div class="lg:col-span-2 space-y-6">
        <div class="flex items-center gap-3 border-l-4 border-indigo-500 pl-4">
          <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Corporate Identity</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Legal Name (PT/CV)</label>
            <input v-model="companyData.name" @change="updateOrgDetail" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100">
          </div>
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Corporate Email</label>
            <input v-model="companyData.email" @change="updateOrgDetail" type="email" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100">
          </div>
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Office Phone Number</label>
            <input v-model="companyData.phone" @change="updateOrgDetail" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100">
          </div>
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Headquarter Address</label>
            <input v-model="companyData.address" @change="updateOrgDetail" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100">
          </div>
        </div>

        <div class="space-y-1 mt-4">
          <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Company Description / Notes</label>
          <textarea v-model="companyData.description" @change="updateOrgDetail" rows="4" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-medium text-slate-700 outline-none focus:ring-2 ring-indigo-100 resize-none uppercase"></textarea>
        </div>
      </div>

      <div class="bg-slate-50/50 border border-slate-100 rounded-[2rem] p-6 space-y-6">
        <h4 class="text-[10px] font-black text-[#2B3674] uppercase text-center tracking-widest">Business Configuration</h4>
        
        <div class="space-y-4">
          <div class="flex flex-col">
            <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Business Sector</label>
            <input v-model="companyData.sector" @change="updateOrgDetail" type="text" placeholder="Misal: Konstruksi, IT..." class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-blue-800 uppercase outline-none focus:ring-2 ring-blue-100">
          </div>
          <div class="flex flex-col">
            <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Partnership Status</label>
            <div class="w-full bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2.5 text-[10px] font-black text-emerald-600 text-center uppercase">
              {{ companyData.status || 'Active Partner' }}
            </div>
          </div>
          <div class="flex flex-col mt-4">
            <span class="text-[9px] font-black text-slate-400 uppercase mb-2 text-center">Data Created At</span>
            <div class="text-xs font-bold text-slate-600 text-center bg-slate-100 py-2 rounded-lg">
              {{ companyData.created_at ? new Date(companyData.created_at).toLocaleDateString('id-ID') : '-' }}
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
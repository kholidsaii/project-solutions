<script setup lang="ts">
import { useRoute } from 'vue-router';
import api from '../../../api/axios';

const props = defineProps(['memberData']);
const emit = defineEmits(['refresh']);
const route = useRoute(); // <-- Tambahkan route

const updateMemberDetail = async () => {
  try {
    // Mengupdate data user menggunakan ID dari URL agar aman
    await api.put(`/users/${route.params.id}`, props.memberData);
    emit('refresh');
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
          <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Personal Identity</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Full Name</label>
            <input v-model="memberData.name" @change="updateMemberDetail" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-bold uppercase outline-none focus:ring-2 ring-indigo-100">
          </div>
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Email Address</label>
            <input v-model="memberData.email" @change="updateMemberDetail" type="email" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100">
          </div>
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Phone Number</label>
            <input v-model="memberData.phone" @change="updateMemberDetail" type="text" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-bold outline-none focus:ring-2 ring-indigo-100">
          </div>
          <div class="space-y-1">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">PT / Organization Affiliation</label>
            <div class="w-full bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-xs font-black text-indigo-600 uppercase">
              {{ memberData.pt_owner_name || 'Independent / Internal' }}
            </div>
          </div>
        </div>

        <div class="space-y-1 mt-4">
          <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Short Biography / Notes</label>
          <textarea v-model="memberData.description" @change="updateMemberDetail" rows="4" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-xs font-medium text-slate-700 outline-none focus:ring-2 ring-indigo-100 resize-none uppercase"></textarea>
        </div>
      </div>

      <div class="bg-slate-50/50 border border-slate-100 rounded-[2rem] p-6 space-y-6">
        <h4 class="text-[10px] font-black text-[#2B3674] uppercase text-center tracking-widest">Work Configuration</h4>
        
        <div class="space-y-4">
          <div class="flex flex-col">
            <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Job Position</label>
            <input v-model="memberData.position" @change="updateMemberDetail" type="text" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-blue-800 uppercase outline-none focus:ring-2 ring-blue-100">
          </div>
          <div class="flex flex-col">
            <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">System Role</label>
            <select v-model="memberData.role" @change="updateMemberDetail" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[10px] font-black text-indigo-600 uppercase outline-none">
              <option value="Admin">Admin</option>
              <option value="Member">Member</option>
              <option value="Super Admin">Super Admin</option>
            </select>
          </div>
          <div class="flex flex-col">
            <label class="text-[9px] font-black text-slate-400 uppercase mb-1 ml-1">Account Status</label>
            <div class="w-full bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2.5 text-[10px] font-black text-emerald-600 text-center uppercase">
              {{ memberData.status || 'Active' }}
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
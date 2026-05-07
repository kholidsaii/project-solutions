<script setup lang="ts">
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../../api/axios';
const props = defineProps(['project']);
const emit = defineEmits(['refresh']);
const route = useRoute();

const showTeamModal = ref(false);
const allUsers = ref<any[]>([]);
const teamForm = ref({ user_id: null, role: '' });

const openAddMemberModal = async () => {
  try {
    const res = await api.get('/users'); 
    allUsers.value = res.data;
    showTeamModal.value = true;
  } catch (e) { console.error(e); }
};

const addMember = async () => {
  if (!teamForm.value.user_id || !teamForm.value.role) return;
  try {
    await api.post(`/projects/${route.params.id}/team`, teamForm.value);
    showTeamModal.value = false;
    teamForm.value = { user_id: null, role: '' };
    emit('refresh');
  } catch (e) { console.error(e); }
};

const removeMember = async (userId: number) => {
  try {
    await api.delete(`/projects/${route.params.id}/team/${userId}`);
    emit('refresh');
  } catch (e) { console.error(e); }
};
</script>

<template>
  <div class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-500 relative">
    <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-inner">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-100"><i class="fas fa-users-cog text-xs"></i></div>
        <div>
          <h4 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Resource Management</h4>
        </div>
      </div>
      <button @click="openAddMemberModal" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest hover:scale-95 transition-all shadow-lg shadow-indigo-100"><i class="fas fa-user-plus mr-2"></i> Add Member</button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="member in project?.team" :key="member.id" class="bg-white border border-slate-100 p-5 rounded-3xl shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-black text-sm uppercase">{{ member.name.substring(0, 2) }}</div>
            <div>
              <h5 class="text-[12px] font-black text-slate-800 uppercase">{{ member.name }}</h5>
              <span class="text-[8px] font-black text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">{{ member.role }}</span>
            </div>
          </div>
          <button @click="removeMember(member.id)" class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-rose-500 p-1"><i class="fas fa-times-circle text-lg"></i></button>
        </div>
      </div>
    </div>

    <div v-if="showTeamModal" class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
      <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
          <h4 class="text-xs font-black uppercase text-indigo-900 tracking-widest">Assign Member</h4>
          <button @click="showTeamModal = false" class="text-slate-400 hover:text-rose-500 transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-8 space-y-5">
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Select Personnel</label>
            <select v-model="teamForm.user_id" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold text-slate-700 outline-none uppercase shadow-inner">
              <option :value="null" disabled>Choose User...</option>
              <option v-for="u in allUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Project Role</label>
            <input v-model="teamForm.role" type="text" placeholder="e.g. SENIOR DEVELOPER" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-[11px] font-bold text-slate-700 outline-none uppercase">
          </div>
        </div>
        <div class="p-6 bg-slate-50 flex gap-3">
          <button @click="addMember" class="flex-1 bg-indigo-600 text-white py-3.5 rounded-xl text-[10px] font-black uppercase shadow-xl shadow-indigo-100">Confirm Assignment</button>
        </div>
      </div>
    </div>
  </div>
</template>


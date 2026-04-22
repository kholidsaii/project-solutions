<script setup lang="ts">
import { useRouter } from 'vue-router';
const router = useRouter();
const logout = () => {
  localStorage.clear();
  router.push('/login');
};
</script>

<template>
  <nav class="fixed z-[100] bg-white border-slate-100 transition-all duration-300
              bottom-0 left-0 w-full h-16 border-t flex flex-row justify-around items-center px-4
              md:top-0 md:left-0 md:w-20 md:h-screen md:border-r md:flex-col md:py-10 md:px-0 md:shadow-sm">
    
    <div class="hidden md:flex flex-none">
       <router-link to="/" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-lg shadow-indigo-100">
          <i class="fas fa-home text-lg"></i>
       </router-link>
    </div>

    <div class="flex-1 flex flex-row md:flex-col justify-around md:justify-center items-center gap-4 md:gap-12 w-full">
      
      <router-link to="/" class="nav-link group" active-class="is-active">
        <i class="fas fa-th-large text-lg"></i>
        <span class="nav-tooltip">Dashboard</span>
      </router-link>

      <router-link to="/activity" class="nav-link group" active-class="is-active">
        <i class="fas fa-box text-lg"></i>
        <span class="nav-tooltip">Activity</span>
      </router-link>

      <router-link to="/analysis" class="nav-link group" active-class="is-active">
        <i class="fas fa-chart-bar text-lg"></i>
        <span class="nav-tooltip">Analysis</span>
      </router-link>

    </div>

    <div class="flex flex-row md:flex-col items-center gap-4 md:gap-10 md:mt-auto flex-none">
      <div class="w-8 h-8 md:w-10 md:h-10 rounded-xl overflow-hidden border-2 border-slate-50 shadow-md">
        <img src="https://ui-avatars.com/api/?name=Kholid+Saifullah&background=4f46e5&color=fff" alt="User" class="w-full h-full">
      </div>
      
      <button @click="logout" class="text-slate-300 hover:text-rose-500 p-2 transition-colors">
        <i class="fas fa-power-off text-lg"></i>
      </button>
    </div>
  </nav>
</template>

<style scoped>
.nav-link {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  color: #cbd5e1; /* slate-300 */
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.is-active {
  background-color: #22c55e !important;
  color: white !important;
  box-shadow: 0 10px 20px -5px rgba(34, 197, 94, 0.4);
}

/* Tooltip Styling */
.nav-tooltip {
  position: absolute;
  left: 60px;
  background-color: #0f172a; /* slate-900 */
  color: white;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  padding: 6px 12px;
  border-radius: 8px;
  white-space: nowrap;
  opacity: 0;
  transform: translateX(-10px);
  transition: all 0.2s ease;
  pointer-events: none;
  display: none;
}

@media (min-width: 768px) {
  .nav-link:hover .nav-tooltip {
    display: block;
    opacity: 1;
    transform: translateX(0);
  }
}

/* Biar ikon nggak goyang pas hover */
.nav-link i {
  position: relative;
  z-index: 2;
}
</style>
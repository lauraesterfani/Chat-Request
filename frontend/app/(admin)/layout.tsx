"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";

export default function AdminDashboardPage() {
  const router = useRouter();

  const handleLogout = () => {
    localStorage.removeItem("jwt_token");
    router.push("/login");
  };

  return (
    <div className="min-h-screen bg-gray-50 font-sans">
      
      {/* Cabeçalho Admin Padronizado */}
      <header className="bg-white shadow-sm sticky top-0 z-10">
        <div className="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
           <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-700 font-bold text-xl">
                C
              </div>
              <div>
                <h1 className="text-xl font-bold text-gray-800"> CRADT</h1>
                <p className="text-xs text-green-600 font-medium">Administração Acadêmica</p>
              </div>
           </div>
           
           {/* Botão de Logout Minimalista (Apenas a Seta como símbolo) */}
           <button 
             onClick={handleLogout} 
             className="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-all duration-200 active:scale-95 flex items-center justify-center"
             title="Sair do Sistema"
           >
             <svg 
               xmlns="http://www.w3.org/2000/svg" 
               fill="none" 
               viewBox="0 0 24 24" 
               strokeWidth={2} 
               stroke="currentColor" 
               className="w-6 h-6"
             >
               <path 
                 strokeLinecap="round" 
                 strokeLinejoin="round" 
                 d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" 
               />
             </svg>
           </button>
        </div>
      </header>

      {/* Conteúdo Principal */}
      <main className="max-w-6xl mx-auto p-8">
        <div className="mb-8">
          <h2 className="text-2xl font-bold text-gray-800">Visão Geral</h2>
          <p className="text-gray-500">Selecione uma ferramenta administrativa abaixo.</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

          {/* Card: Visualizar Requerimentos */}
          <Link
            href="/requests" 
            className="group bg-white p-6 rounded-2xl shadow-xl border border-gray-100 hover:shadow-2xl hover:border-green-200 transition-all cursor-pointer relative overflow-hidden"
          >
            <div className="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div className="relative z-10">
                <div className="w-12 h-12 bg-green-100 text-green-700 rounded-xl flex items-center justify-center mb-4 text-2xl">
                  📂
                </div>
                <h3 className="text-lg font-bold text-gray-800 group-hover:text-green-700 transition-colors">Requerimentos</h3>
                <p className="text-sm text-gray-500 mt-2">
                  Visualize, analise e defira as solicitações dos alunos.
                </p>
            </div>
          </Link>

          {/* Card: Abrir Chamado */}
          <Link
            href="/requests/new"
            className="group bg-white p-6 rounded-2xl shadow-xl border border-gray-100 hover:shadow-2xl hover:border-yellow-200 transition-all cursor-pointer relative overflow-hidden"
          >
             <div className="absolute top-0 right-0 w-24 h-24 bg-yellow-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
             <div className="relative z-10">
                <div className="w-12 h-12 bg-yellow-100 text-yellow-700 rounded-xl flex items-center justify-center mb-4 text-2xl">
                  ✏️
                </div>
                <h3 className="text-lg font-bold text-gray-800 group-hover:text-yellow-700 transition-colors">Abrir Chamado</h3>
                <p className="text-sm text-gray-500 mt-2">
                  Criar um requerimento manualmente para um aluno.
                </p>
             </div>
          </Link>

        </div>
      </main>
    </div>
  );
}
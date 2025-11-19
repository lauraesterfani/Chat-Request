'use client' 

import { ReactNode, useEffect } from 'react';
import { useRouter } from 'next/navigation'; 
import { useAuth } from '../context/AuthContext'; 

// Importante: A sua função useAuth deve retornar um objeto com { user: AuthUser | null, isLoading: boolean }
// onde AuthUser inclui a propriedade 'role'.

// Componente para o ícone do menu (Placeholder simples)
const HiMenu = () => <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6"><path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>;

export default function StudentLayout({ children }: { children: ReactNode }) {
  const { user, isLoading } = useAuth();
  const router = useRouter();

  // Proteção de Rota: Redirecionamento 
  useEffect(() => {
    // 1. Lógica principal: Se não estiver a carregar E o utilizador não existir ou o role não for 'student'
    if (!isLoading && (!user || user.role !== 'student')) {
      console.log('Acesso restrito ou não autenticado. Redirecionando para /login.');
      router.replace('/login');
      return; 
    }
    
    // Se estiver a carregar ou o utilizador for 'student', não faz nada.
  }, [user, isLoading, router]); 

  // 1. Enquanto está a carregar, mostra o loading.
  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen bg-gray-100">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
        <p className="mt-4 text-gray-700">Verificando Permissões...</p>
      </div>
    );
  }
  
  // 2. Se o carregamento terminou E o utilizador não é um aluno (vai ser redirecionado pelo useEffect), retorna null.
  const isAuthorized = user && user.role === 'student';
  if (!isAuthorized) {
    // Isto evita que o conteúdo não autorizado seja renderizado por um breve momento antes do redirect.
    return null; 
  }

  // 3. Se for um aluno AUTORIZADO, mostra o layout
  return (
    <div className="min-h-screen bg-gray-100">
      
      {/* ===== BARRA DE NAVEGAÇÃO SUPERIOR ===== */}
      <nav className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            {/* Ícone do Menu (esquerda) */}
            <div className="flex items-center">
              <button className="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                <HiMenu /> 
              </button>
            </div>
            
            {/* Nome do utilizador (direita) */}
            <div className="flex items-center">
              <span className="text-sm text-gray-700 font-medium">Bem-vindo, {user.name}</span>
            </div>
          </div>
        </div>
      </nav>

      {/* ===== CONTEÚDO DA PÁGINA (MAIN) ===== */}
      <main className="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        {children}
      </main>
    </div>
  );
}
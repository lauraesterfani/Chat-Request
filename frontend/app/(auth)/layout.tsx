'use client' 

import { ReactNode, useEffect } from 'react';
import { useAuth } from '../context/AuthContext'; 
import { useRouter } from 'next/navigation';

export default function AuthLayout({ children }: { children: ReactNode }) {
  const { user, isLoading } = useAuth();
  const router = useRouter();
  
  // Proteção de Rota Inversa: Redireciona para o painel se já estiver autenticado
  useEffect(() => {
    // Se o carregamento terminou E o utilizador ESTÁ autenticado
    if (!isLoading && user) {
      console.log(`Utilizador já autenticado. Redirecionando para /me.`);
      // Redireciona para a página principal do utilizador logado
      router.replace('/me'); 
    }
  }, [user, isLoading, router]); 

  // 1. Enquanto está a carregar OU se o utilizador já está logado (redirecionando), mostra o loading
  if (isLoading || user) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen bg-gray-100">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-green-600"></div>
        <p className="mt-4 text-gray-700">A processar o estado de autenticação...</p>
      </div>
    );
  }
  
  // 2. Se não estiver logado (user é null), mostra o layout de autenticação (formulário centralizado)
  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-100 p-4">
      <div className="w-full max-w-md bg-white p-8 rounded-xl shadow-2xl border border-gray-200">
        {/* Aqui será injetado o conteúdo de Login ou Signup */}
        {children}
      </div>
    </div>
  );
}
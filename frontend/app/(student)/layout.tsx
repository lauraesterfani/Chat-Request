'use client' // Layouts com proteção de rota e menus precisam ser Client Components

import { ReactNode } from 'react';
import { useAuth } from '@/context/AuthContext'; // Para proteção de rota
import { useRouter } from 'next/navigation';

// Ícone para o menu
import { HiMenu } from 'react-icons/hi';

export default function StudentLayout({ children }: { children: ReactNode }) {
  const { user, isLoading } = useAuth();
  const router = useRouter();

  // Proteção de Rota:
  // 1. Se estiver a carregar, não mostres nada
  if (isLoading) {
    return null; // ou um ecrã de loading
  }

  // 2. Se não estiver a carregar E o utilizador não for um aluno...
  if (!user || user.role !== 'student') { //
    // ...expulsa-o para o login.
    if (typeof window !== 'undefined') {
      router.push('/');
    }
    return null; // Não renderiza nada enquanto redireciona
  }

  // 3. Se for um aluno, mostra o layout
  return (
    <div className="min-h-screen bg-gray-100">
      
      {/* ===== BARRA DE NAVEGAÇÃO SUPERIOR ===== */}
      <nav className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            {/* Ícone do Menu (esquerda) */}
            <div className="flex items-center">
              <button className="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                <HiMenu size={24} />
              </button>
            </div>
            
            {/* TODO: Adicionar ícone do utilizador ou nome (direita) */}
            <div className="flex items-center">
              <span className="text-sm text-gray-700">Bem-vindo, {user.name}</span>
            </div>
          </div>
        </div>
      </nav>

      {/* ===== CONTEÚDO DA PÁGINA (MAIN) ===== */}
      <main className="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        {/* O 'children' é a página (ex: /chat) que será injetada aqui */}
        {children}
      </main>
    </div>
  );
}
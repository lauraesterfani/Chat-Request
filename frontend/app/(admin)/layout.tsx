'use client' // Layouts com botões (logout) precisam ser Client Components

import { ReactNode } from 'react';
import { useAuth } from '@/context/AuthContext'; // Para o logout
import { useRouter } from 'next/navigation';
import Image from 'next/image';
import Link from 'next/link';

// Ícone para o botão de logout
import { IoLogOutOutline } from 'react-icons/io5';

export default function AdminLayout({ children }: { children: ReactNode }) {
  const { logout, user } = useAuth();
  const router = useRouter();

  const handleLogout = () => {
    // Chame a função de logout do nosso cérebro de autenticação
    logout(); 
  };

  // Proteção de Rota Simples:
  // Se o Next.js tentar renderizar este layout e o usuário não for admin/staff,
  // (ex: um aluno a tentar aceder /dashboard manualmente), redireciona-o.
  if (!user || (user.role !== 'admin' && user.role !== 'staff')) {
    // Idealmente, isto mostraria um ecrã de "Acesso Negado",
    // mas por agora vamos apenas enviá-lo para o login.
    if (typeof window !== 'undefined') { // Garante que o código só corre no navegador
      router.push('/');
    }
    return null; // Não renderiza nada enquanto redireciona
  }

  return (
    <div className="flex h-screen bg-gray-100">
      
      {/* ===== BARRA LATERAL (ASIDE) ===== */}
      <aside className="w-64 bg-[#1a472a] text-white flex flex-col p-6">
        {/* Logo e Título do Painel */}
        <div className="flex items-center gap-3 px-2">
          <Image
            src="/mascote.png" // Da pasta 'frontend/public'
            alt="Mascote"
            width={40}
            height={40}
          />
          <h1 className="text-2xl font-bold">Painel</h1>
        </div>

        {/* Links de Navegação (Exemplo) */}
        <nav className="flex-1 mt-10 space-y-2">
          <Link href="/dashboard" className="block py-2 px-4 rounded hover:bg-green-700">
            Lista de Requerimentos
          </Link>
          <Link href="/dashboard/users" className="block py-2 px-4 rounded hover:bg-green-700">
            Usuários
          </Link>
          <Link href="/dashboard/types" className="block py-2 px-4 rounded hover:bg-green-700">
            Tipos de Requerimento
          </Link>
        </nav>

        {/* Botão de Logout (Fixo em baixo) */}
        <div className="mt-auto">
          <button
            onClick={handleLogout}
            className="flex items-center gap-2 w-full text-left py-2 px-4 rounded text-gray-300 hover:bg-green-700 hover:text-white"
          >
            <IoLogOutOutline size={20} />
            Fazer Logout
          </button>
        </div>
      </aside>

      {/* ===== CONTEÚDO PRINCIPAL (MAIN) ===== */}
      <main className="flex-1 p-10 overflow-y-auto">
        {/* O 'children' é a página que será injetada aqui (ex: a tabela) */}
        {children}
      </main>
    </div>
  );
}
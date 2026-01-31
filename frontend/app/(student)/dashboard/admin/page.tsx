"use client";

import Link from "next/link";

// Simulação de controle de acesso (substitua com sua lógica real)
const isAdmin = true; // Altere para false para testar como não-admin

export default function DashboardPage() {
  return (
    <div className="min-h-screen flex bg-[#F4F6F8]">
      {/* Painel esquerdo */}
      <aside className="w-64 bg-[#0B0D3A] text-white flex flex-col p-6 shadow-lg">
        <h1 className="text-2xl font-bold mb-4">Chat Request</h1>
        <p className="text-sm opacity-80 mb-6">
          Painel de gerenciamento de requerimentos acadêmicos
        </p>
        <nav className="space-y-4">
          
         
        </nav>
      </aside>

      {/* Conteúdo principal */}
      <main className="flex-1 p-10">
        <h2 className="text-3xl font-semibold mb-8 text-[#0B0D3A]">Painel principal</h2>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl">

          {/* Cadastrar staff */}
          <Link
            href="/signup/staff"
            className="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border border-gray-200"
          >
            <h3 className="text-lg font-semibold text-[#0B0D3A]">Cadastrar administrador</h3>
            <p className="text-sm text-gray-600">
              Adicionar novos membros da equipe
            </p>
          </Link>

          {/* Visualizar staffs */}
          <Link
            href="/staffs"
            className="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border border-gray-200"
          >
            <h3 className="text-lg font-semibold text-[#0B0D3A]">Visualizar administradores</h3>
            <p className="text-sm text-gray-600">
              Ver administradores já cadastrados
            </p>
          </Link>

          {/* Visualizar requerimentos */}
          <Link
            href="/request"
            className="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border border-gray-200"
          >
            <h3 className="text-lg font-semibold text-[#0B0D3A]">Visualizar requerimentos</h3>
            <p className="text-sm text-gray-600">
              Acompanhar solicitações dos alunos
            </p>
          </Link>

          {/* Novo requerimento - visível apenas para não-admins */}
          {!isAdmin && (
            <Link
              href="/requests/new"
              className="bg-[#1A73E8] text-white rounded-xl shadow-md p-6 hover:opacity-90 transition"
            >
              <h3 className="text-lg font-semibold">Novo requerimento</h3>
              <p className="text-sm opacity-90">
                Criar uma nova solicitação
              </p>
            </Link>
          )}

        </div>
      </main>
    </div>
  );
}

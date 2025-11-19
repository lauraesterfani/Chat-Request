'use client';

// CORREÇÃO: Adicionando novamente a extensão '.tsx' para resolver o caminho corretamente.
import React, { useEffect } from 'react';
import { useAuth } from '../../context/AuthContext'; 

/**
 * Componente principal da Dashboard, que mostra os dados do utilizador autenticado.
 * Inclui logs de debug para diagnosticar o estado de autenticação.
 */
const Dashboard: React.FC = () => {
    // 1. Obter estado do contexto de autenticação
    const { user, isAuthenticated, isLoading, logout } = useAuth();

    

    // 2. Estado de Carregamento
    if (isLoading) {
        return (
            <div className="flex justify-center items-center min-h-screen">
                <p className="text-xl font-semibold text-gray-600">A verificar autenticação e a carregar dashboard...</p>
            </div>
        );
    }

    // 3. Estado de Não Autenticado
    if (!isAuthenticated) {
        console.error("Dashboard renderizada mas utilizador não autenticado. Redirecionamento forçado para /login.");
        // Redireciona para o login de forma segura
        if (typeof window !== 'undefined') {
            // Atrasamos o redirect para o utilizador poder ler o log na consola
            setTimeout(() => {
                window.location.href = '/login';
            }, 1500);
        }
        return (
            <div className="text-center p-8 min-h-screen flex flex-col justify-center items-center bg-red-50">
                <h1 className="text-4xl font-extrabold text-red-700">Acesso Negado</h1>
                <p className="text-xl text-red-500 mt-2">O token de sessão é inválido ou expirou.</p>
                <p className="text-lg text-gray-600 mt-4">A ser redirecionado(a) para a página de login...</p>
            </div>
        ); 
    }

    // 4. Teste de Sanidade (O user deveria existir se isAuthenticated for true)
    if (!user) {
        console.error("⚠️ ERRO CRÍTICO: isAuthenticated é TRUE mas o objeto 'user' é NULL. A fazer Logout forçado.");
        logout();
        return <p className="text-center mt-20 text-red-500">Erro de autenticação. A limpar sessão...</p>;
    }
    
    // 5. Renderização dos Dados Reais
    return (
        <div className="min-h-screen bg-gray-50 p-4 sm:p-8">
            <div className="max-w-4xl mx-auto bg-white shadow-xl rounded-lg p-6 sm:p-10">
                <header className="flex justify-between items-center border-b pb-4 mb-6">
                    <h1 className="text-3xl font-extrabold text-green-700">
                        Bem-vindo(a), {user.name || 'Utilizador(a) sem Nome'}!
                    </h1>
                    <button 
                        onClick={logout}
                        className="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition duration-150 shadow-md"
                    >
                        Sair
                    </button>
                </header>

                <section className="space-y-4">
                    <h2 className="text-2xl font-semibold text-gray-800 border-b pb-2 mb-4">Detalhes da Conta</h2>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <DataItem label="Email" value={user.email} />
                        <DataItem label="CPF" value={user.cpf} />
                        <DataItem label="Telefone" value={user.phone} />
                        <DataItem label="Nível de Acesso (Role)" value={user.role} />
                        <DataItem label="Data de Nascimento" value={user.birthday} />
                        <DataItem label="ID do Utilizador" value={user.id?.toString()} /> 
                    </div>
                </section>

                <footer className="mt-8 pt-4 border-t text-sm text-gray-500">
                    <p>Esta é a sua área pessoal. Dados carregados com sucesso da API Laravel na porta 8001.</p>
                </footer>
            </div>
        </div>
    );
};

// Componente auxiliar para estilizar os itens de dados
const DataItem: React.FC<{ label: string, value: string | undefined }> = ({ label, value }) => (
    <div className="bg-green-50 p-3 rounded-md border border-green-200">
        <p className="text-xs font-medium text-green-700 uppercase">{label}</p>
        <p className="text-base font-semibold text-gray-900 break-words">{value || 'N/A'}</p>
    </div>
);

export default Dashboard;
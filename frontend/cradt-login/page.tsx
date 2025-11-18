'use client'

import { useState, useEffect } from 'react';
// Caminho de importação corrigido (Ajustado para '../context/AuthContext')
import { useAuth } from '../context/AuthContext'; 

// =================================================================================
// MOCK DO ROUTER (Necessário para a navegação)
// =================================================================================
const useRouter = () => ({
    push: (path: string) => {
        if (typeof window !== 'undefined') {
            window.location.href = path; 
        }
    },
});

// =================================================================================
// DADOS MOCK PARA SIMULAR LOGIN DE SUCESSO DO CRADT (STAFF)
// =================================================================================
const MOCK_STAFF_USER = {
    id: 'user-staff-456',
    name: 'Ana Funcionária CRADT',
    email: 'ana.staff@cradt.com',
    cpf: '987.654.321-00',
    phone: '5511888888888',
    role: 'staff' as const, // Força 'staff'
    birthday: '1990-05-15',
};
const MOCK_TOKEN = 'mock-jwt-token-for-testing-staff';

// =================================================================================
// COMPONENTE PRINCIPAL DE LOGIN
// =================================================================================

export default function CradtLoginPage() {
    const router = useRouter();
    const { login, isAuthenticated, isLoading } = useAuth();
    
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | null>(null);

    // 1. Redirecionamento se já estiver autenticado
    useEffect(() => {
        if (!isLoading && isAuthenticated) {
            // O AuthContext já cuida do redirecionamento por role (Staff -> /dashboard)
            router.push('/dashboard'); 
        }
    }, [isLoading, isAuthenticated, router]);
    
    // 2. Handler de Login
    const handleLogin = (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        
    }
       

    if (isLoading || isAuthenticated) {
        return (
            <div className="flex flex-col items-center justify-center min-h-screen bg-gray-50">
                <p className="text-xl text-gray-500 mt-4">A carregar...</p>
            </div>
        );
    }

    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-100 p-4">
            <div className="w-full max-w-md bg-white p-8 rounded-xl shadow-2xl border border-gray-200">
                <h1 className="text-3xl font-bold text-center text-red-700 mb-2">Acesso do CRADT/Staff</h1>
                <p className="text-center text-gray-500 mb-8">Insira as suas credenciais de funcionário.</p>

                <form onSubmit={handleLogin} className="space-y-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="email">Email de Acesso</label>
                        <input
                            id="email"
                            type="text"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-red-700 focus:border-red-700"
                            placeholder="Email ou Matrícula (Mock: cradt)"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="password">Senha</label>
                        <input
                            id="password"
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-red-700 focus:border-red-700"
                            placeholder="Senha (Mock: admin)"
                            required
                        />
                    </div>
                    
                    {error && (
                        <div className="p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
                            {error}
                        </div>
                    )}

                    <button
                        type="submit"
                        className="w-full py-3 px-4 bg-red-700 text-white font-bold rounded-lg shadow-xl hover:bg-red-800 transition duration-300 text-lg"
                    >
                        Entrar como CRADT
                    </button>
                </form>

                {/* --- Link para o Aluno --- */}
                <div className="mt-8 pt-4 border-t border-gray-200 text-center">
                    <p className="text-sm text-gray-600">
                        É um aluno?{' '}
                        <a 
                            href="/login" 
                            className="font-semibold text-[#388e3c] hover:text-[#1a472a] transition duration-200"
                        >
                            Voltar para o Login do Aluno.
                        </a>
                    </p>
                </div>
            </div>
        </div>
    );
}
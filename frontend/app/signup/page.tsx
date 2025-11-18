'use client'

import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext'; 
import { User, UserRole } from '../../context/AuthContext'; // Importar os tipos (com .tsx)

// =================================================================================
// CONFIGURAÇÃO DA API - ALTERE ESTA URL!
// =================================================================================

// *** IMPORTANTE: Substitua 'http://seu-backend.com/api/register' pela sua URL REAL ***
const REGISTER_API_URL = 'http://localhost:8002/api/register'; 

// =================================================================================
// Ícones
// =================================================================================

const UserPlusIcon = (props: React.SVGProps<SVGSVGElement>) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <line x1="17" x2="17" y1="10" y2="16" />
        <line x1="14" x2="20" y1="13" y2="13" />
    </svg>
);

const LoaderCircle = (props: React.SVGProps<SVGSVGElement>) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="animate-spin">
        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
    </svg>
);

// MOCK DO ROUTER
const useRouter = () => ({
    push: (path: string) => {
        if (typeof window !== 'undefined') {
            window.location.href = path; 
        }
    },
});

// =================================================================================
// COMPONENTE PRINCIPAL: SignupPage
// =================================================================================

export default function SignupPage() {
    const { login, isAuthenticated, isLoading } = useAuth();
    const router = useRouter();

    const [name, setName] = useState('');
    const [cpf, setCpf] = useState('');
    const [email, setEmail] = useState('');
    // NOVOS CAMPOS
    const [phone, setPhone] = useState('');
    const [birthday, setBirthday] = useState(''); // Formato YYYY-MM-DD
    
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [isProcessing, setIsProcessing] = useState(false);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setSuccessMessage(null);

        if (password !== passwordConfirmation) {
            setError("As senhas não correspondem.");
            return;
        }

        // Validação de todos os campos obrigatórios
        if (!name || !cpf || !email || !password || !phone || !birthday) {
            setError("Por favor, preencha todos os campos obrigatórios (Nome, CPF, Email, Telefone, Data de Nascimento e Senha).");
            return;
        }

        setIsProcessing(true);

        try {
            // =====================================================================
            // CHAMADA FETCH REAL PARA O SEU ENDPOINT DE REGISTO (Laravel)
            // =====================================================================
            const response = await fetch(REGISTER_API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                // Payload atualizado para incluir 'phone' e 'birthday'
                body: JSON.stringify({ 
                    name, 
                    cpf, 
                    email, 
                    phone,
                    birthday,
                    password, 
                    password_confirmation: passwordConfirmation 
                }) 
            });

            const data = await response.json();

            if (!response.ok) {
                 // Tratar erros de validação (ex: CPF já existe)
                 const message = data.message || 'Erro no processamento do cadastro. Verifique a consola.';
                 throw new Error(message);
            }
            
            // ATENÇÃO: Assumimos que o registo bem-sucedido pode retornar o token e user
            if (data.token && data.user) {
                // Se o registo fizer login automático:
                login(data.user as User, data.token);
                // A navegação ocorre dentro do AuthContext
            } else {
                // Se o registo NÃO fizer login automático:
                setSuccessMessage("Cadastro realizado com sucesso! Pode agora fazer o login.");
                // Limpar campos para um novo registo ou redirecionar
                setTimeout(() => router.push('/login'), 2000); 
            }

        } catch (e) {
            const errorMessage = e instanceof Error ? e.message : "Ocorreu um erro ao comunicar com o servidor. Verifique a URL e CORS.";
            console.error("Erro durante o processo de cadastro:", e);
            setError(errorMessage);
        } finally {
             setIsProcessing(false);
        }
    };

    if (!isLoading && isAuthenticated) {
        // Se já estiver autenticado, redireciona para a página principal
        router.push('/me');
        return null;
    }


    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
            <div className="w-full max-w-lg bg-white p-8 rounded-xl shadow-2xl border border-gray-100">
                <div className="flex flex-col items-center mb-6">
                    <UserPlusIcon className="w-10 h-10 text-[#1a472a] mb-2" />
                    <h1 className="text-3xl font-extrabold text-[#1a472a]">Novo Cadastro de Aluno (Signup)</h1>
                    <p className="text-sm text-gray-500 mt-1">Endpoint: {REGISTER_API_URL}</p>
                </div>

                {error && (
                    <div className="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg border border-red-400">
                        {error}
                    </div>
                )}

                {successMessage && (
                    <div className="p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg border border-green-400">
                        {successMessage}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Linha 1: Nome e Email */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input
                            type="text" 
                            placeholder="Nome Completo"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            required
                            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#388e3c] focus:border-transparent transition"
                        />
                        <input
                            type="email" 
                            placeholder="Email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            required
                            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#388e3c] focus:border-transparent transition"
                        />
                    </div>

                    {/* Linha 2: CPF, Telefone e Data de Nascimento */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <input
                            type="text" 
                            placeholder="CPF (apenas números)"
                            value={cpf}
                            onChange={(e) => setCpf(e.target.value)}
                            required
                            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#388e3c] focus:border-transparent transition"
                        />
                         <input
                            type="tel" 
                            placeholder="Telefone"
                            value={phone}
                            onChange={(e) => setPhone(e.target.value)}
                            required
                            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#388e3c] focus:border-transparent transition"
                        />
                        <div className="relative">
                            <input
                                type="date" 
                                placeholder="Data Nasc."
                                value={birthday}
                                onChange={(e) => setBirthday(e.target.value)}
                                required
                                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#388e3c] focus:border-transparent transition text-gray-500"
                            />
                        </div>
                    </div>

                    {/* Linha 3: Senhas */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input
                            type="password"
                            placeholder="Senha"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#388e3c] focus:border-transparent transition"
                        />
                        <input
                            type="password"
                            placeholder="Confirmar Senha"
                            value={passwordConfirmation}
                            onChange={(e) => setPasswordConfirmation(e.target.value)}
                            required
                            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#388e3c] focus:border-transparent transition"
                        />
                    </div>
                    
                    <button
                        type="submit"
                        disabled={isProcessing}
                        className={`w-full py-3 mt-6 rounded-lg text-white font-bold transition duration-300 flex items-center justify-center ${
                            isProcessing ? 'bg-gray-400 cursor-not-allowed' : 'bg-[#1a472a] hover:bg-[#388e3c] shadow-lg'
                        }`}
                    >
                        {isProcessing ? (
                            <>
                                <LoaderCircle className="w-5 h-5 mr-2 text-white" />
                                A Cadastrar...
                            </>
                        ) : (
                            'Criar Conta'
                        )}
                    </button>
                </form>

                <p className="mt-6 text-center text-sm text-gray-500">
                    Já tem uma conta? 
                    <a onClick={() => router.push('/login')} className="text-[#388e3c] hover:underline cursor-pointer font-medium ml-1">
                        Fazer Login
                    </a>
                </p>
            </div>
        </div>
    );
}
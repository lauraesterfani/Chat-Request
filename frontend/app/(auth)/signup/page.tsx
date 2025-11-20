'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext'; // Caminho correto
import Link from 'next/link';
import Image from 'next/image';

// =================================================================================
// Ícones
// =================================================================================

const LoaderCircle = (props: React.SVGProps<SVGSVGElement>) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="animate-spin">
        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
    </svg>
);

// =================================================================================
// COMPONENTE PRINCIPAL: SignupPage
// =================================================================================

export default function SignupPage() {
    // 1. Usamos o hook normalmente, sem 'as unknown as...'
    // O AuthContext já exporta a função 'register' corretamente.
    const { register, isAuthenticated, isLoading } = useAuth();
    
    const [name, setName] = useState('');
    const [cpf, setCpf] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [birthday, setBirthday] = useState(''); 
    
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [isProcessing, setIsProcessing] = useState(false);

    // Redirecionamento automático se já estiver logado
    // (Nota: Em Next.js 13+ com App Router, o ideal é usar useRouter do 'next/navigation')
    // Mas manteremos simples aqui conforme o seu padrão.
    useEffect(() => {
        if (!isLoading && isAuthenticated) {
            window.location.href = '/me'; // Ou use router.push('/me') se importar o useRouter
        }
    }, [isLoading, isAuthenticated]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);

        if (password !== passwordConfirmation) {
            setError("As senhas não correspondem.");
            return;
        }

        if (!name || !cpf || !email || !password || !phone || !birthday) {
            setError("Por favor, preencha todos os campos obrigatórios.");
            return;
        }

        setIsProcessing(true);

        // 2. Montamos o objeto com os dados
        const userData = { 
            name, 
            cpf, 
            email, 
            phone,
            birthday,
            password, 
            password_confirmation: passwordConfirmation 
        };

        try {
            // 3. Chamamos a função register do Contexto
            // Ela já faz o fetch, e se der certo, já salva o token e o user no estado.
            const result = await register(userData);

            if (result.success) {
                // Sucesso! O AuthContext já atualizou o estado 'user' e 'token'.
                // O useEffect acima vai detetar 'isAuthenticated' como true e redirecionar.
                console.log("Cadastro realizado com sucesso!");
            } else {
                // Erro retornado pela API (ex: CPF duplicado)
                setError(result.message || "Erro ao realizar cadastro.");
            }
        } catch (err) {
            console.error(err);
            setError("Ocorreu um erro inesperado.");
        } finally {
            setIsProcessing(false);
        }
    };

    // Se estiver a carregar ou já autenticado, não mostra o form (evita flash)
    if (isLoading || isAuthenticated) {
        return null; 
    }

    return (
        <div className="min-h-screen w-full flex flex-col lg:flex-row bg-white font-sans">
            
            {/* LADO ESQUERDO: BRANDING (Apenas Desktop) */}
            <div className="hidden lg:flex lg:w-[45%] xl:w-[40%] bg-[#15803d] relative flex-col justify-between p-12 overflow-hidden h-screen sticky top-0">
                <div className="relative z-10 flex items-center gap-3">
                   <div className="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center text-white font-bold">CR</div>
                   <span className="text-white font-bold text-xl tracking-tight">Chat Request</span>
                </div>

                <div className="relative z-10 flex flex-col items-start">
                   <div className="w-64 h-64 relative mb-8 self-center">
                     <Image 
                        src="/mascote.png" 
                        alt="Mascote" 
                        fill
                        className="object-contain drop-shadow-2xl"
                        priority
                     />
                   </div>
                   <h2 className="text-4xl font-bold text-white mb-4 leading-tight">
                     Sua jornada acadêmica,<br/>simplificada.
                   </h2>
                   <p className="text-green-100 text-lg leading-relaxed max-w-md">
                     Crie sua conta em segundos e tenha acesso direto à secretaria sem filas e sem burocracia.
                   </p>
                </div>

                <div className="relative z-10 text-sm text-green-200/80">
                  © 2025 Chat Request Inc.
                </div>

                {/* Elementos Decorativos de Fundo */}
                <div className="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('/grid-pattern.svg')]"></div>
                <div className="absolute bottom-0 right-0 w-[500px] h-[500px] bg-green-400 rounded-full blur-[120px] opacity-20 translate-x-1/3 translate-y-1/3"></div>
            </div>

            {/* LADO DIREITO: FORMULÁRIO */}
            <div className="flex-1 flex flex-col justify-center items-center px-6 py-12 md:px-12 lg:px-16 xl:px-24 overflow-y-auto">
                <div className="w-full max-w-xl">
                    
                    <div className="lg:hidden mb-10 text-center">
                       <Link href="/" className="inline-flex items-center gap-2 justify-center mb-4">
                          <div className="w-10 h-10 bg-[#15803d] rounded-xl flex items-center justify-center">
                            <span className="text-white font-bold">CR</span>
                          </div>
                          <span className="text-2xl font-bold text-[#0f172a]">Chat Request</span>
                       </Link>
                       <h1 className="text-3xl font-bold text-[#0f172a] mt-2">Criar conta</h1>
                    </div>

                    <div className="hidden lg:block mb-10">
                      <h1 className="text-3xl font-bold text-[#0f172a]">Criar conta</h1>
                      <p className="text-gray-500 mt-2 text-lg">Preencha seus dados para começar.</p>
                    </div>

                    {error && (
                      <div className="mb-8 p-4 bg-red-50 border border-red-100 rounded-xl flex items-start gap-3 text-red-700">
                        <span>{error}</span>
                      </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        
                        {/* Nome */}
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Nome Completo</label>
                            <input
                                type="text" 
                                placeholder="Ex: Maria Oliveira"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                required
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                            />
                        </div>

                        {/* Grid Email + Tel */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">E-mail</label>
                                <input
                                  type="email"
                                  value={email}
                                  onChange={(e) => setEmail(e.target.value)}
                                  className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                  placeholder="email@exemplo.com"
                                  required
                                />
                             </div>
                             <div>
                                <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Telefone</label>
                                <input
                                  type="tel"
                                  value={phone}
                                  onChange={(e) => setPhone(e.target.value)}
                                  className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                  placeholder="(00) 00000-0000"
                                  required
                                />
                             </div>
                        </div>

                        {/* Grid CPF + Data */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                              <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">CPF</label>
                              <input
                                type="text"
                                value={cpf}
                                onChange={(e) => setCpf(e.target.value)}
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                placeholder="000.000.000-00"
                                required
                              />
                            </div>
                            <div>
                              <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Data de Nascimento</label>
                              <input
                                type="date"
                                value={birthday}
                                onChange={(e) => setBirthday(e.target.value)}
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-600 font-medium"
                                required
                              />
                            </div>
                        </div>

                        {/* Senhas */}
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Senha</label>
                            <input
                                type="password"
                                placeholder="Mínimo 8 caracteres"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Confirmar Senha</label>
                            <input
                                type="password"
                                placeholder="Repita a senha"
                                value={passwordConfirmation}
                                onChange={(e) => setPasswordConfirmation(e.target.value)}
                                required
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                            />
                        </div>
                        
                        <button
                            type="submit"
                            disabled={isProcessing}
                            className={`w-full py-4 rounded-2xl text-white font-bold text-lg transition duration-300 flex items-center justify-center mt-6 shadow-lg shadow-green-700/20 hover:shadow-green-700/40 active:scale-[0.98] ${
                                isProcessing ? 'bg-gray-400 cursor-not-allowed' : 'bg-[#15803d] hover:bg-[#166534]'
                            }`}
                        >
                            {isProcessing ? (
                                <>
                                    <LoaderCircle className="w-5 h-5 mr-2 text-white" />
                                    A Cadastrar...
                                </>
                            ) : (
                                'Criar minha conta'
                            )}
                        </button>
                    </form>

                    <p className="mt-10 text-center text-gray-600">
                        Já tem uma conta? 
                        <Link href="/login" className="text-[#15803d] hover:underline hover:text-[#166534] font-bold ml-1">
                            Fazer Login
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
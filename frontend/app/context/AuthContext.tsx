'use client';

import React, { useState, useEffect, createContext, useContext, useCallback, useMemo } from 'react';

// === CONFIGURAÇÃO GLOBAL DA APLICAÇÃO ===
const BASE_URL = 'http://localhost:8000/api'; // URL de base fornecida pelo utilizador
const REGISTER_URL = `${BASE_URL}/register`; // Endpoint para Cadastro
const LOGIN_URL = `${BASE_URL}/login`; // Endpoint para Login
const ME_URL = `${BASE_URL}/me`; // Endpoint para Obter Dados do Utilizador
const JWT_STORAGE_KEY = 'jwt_token';

// =================================================================================
// 1. Definições de Tipos e Interfaces (Replicando as fornecidas pelo utilizador)
// =================================================================================

interface User {
    id: number | string; 
    name: string;
    email: string;
    cpf: string; 
    phone: string;
    role: string;
    birthday: string; 
    created_at?: string; 
    updated_at?: string; 
    [key: string]: any; 
}

interface AuthContextType {
    user: User | null;
    token: string | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    setToken: (newToken: string | null) => void; 
    logout: () => void;
    login: (credentials: { cpf: string, password: string }) => Promise<{ success: boolean, message?: string }>;
    // Função register atualizada para retornar sucesso e mensagem, tal como o login
    register: (data: any) => Promise<{ success: boolean, message?: string }>; 
}

interface IconProps extends React.SVGProps<SVGSVGElement> {
    size?: number; // Propriedade 'size' para controlar o tamanho
}

// =================================================================================
// 2. Criação do Contexto e Hook
// =================================================================================

const defaultAuthContext: AuthContextType = {
    user: null,
    token: null,
    isAuthenticated: false,
    isLoading: true, 
    setToken: () => {}, 
    logout: () => {},
    login: async () => ({ success: false }),
    register: async () => ({ success: false }), // Atualizado para corresponder ao novo tipo
};

export const AuthContext = createContext<AuthContextType>(defaultAuthContext);
export const useAuth = () => useContext(AuthContext); 

// =================================================================================
// 3. Provedor de Autenticação (AuthProvider) - Lógica Centralizada
// =================================================================================

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [token, setTokenState] = useState<string | null>(null);
    const [user, setUser] = useState<User | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    const setToken = useCallback((newToken: string | null) => {
        setTokenState(newToken);
        if (newToken) {
            localStorage.setItem(JWT_STORAGE_KEY, newToken);
        } else {
            localStorage.removeItem(JWT_STORAGE_KEY);
        }
    }, []);

    const logout = useCallback(() => {
        setToken(null);
        setUser(null);
        console.log("[AUTH] Sessão encerrada. Token removido.");
    }, [setToken]);
    
    // -----------------------------------------------------------------
    // FUNÇÃO DE REGISTO (Implementação completa com fetch)
    // -----------------------------------------------------------------
    
    const register = useCallback(async (data: any): Promise<{ success: boolean, message?: string }> => {
        console.log(`[AUTH: REGISTER] 📞 Tentativa de cadastro. URL: ${REGISTER_URL}`);
        setIsLoading(true);

        try {
            const response = await fetch(REGISTER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data), 
            });

            const responseData = await response.json();
            
            console.log(`[AUTH: REGISTER] 🔄 Status HTTP: ${response.status}`);

            if (response.ok) {
                // Se o registo for bem-sucedido e devolver token, loga automaticamente
                if (responseData.token && responseData.user) {
                    setToken(responseData.token);
                    setUser(responseData.user);
                    console.log("[AUTH: REGISTER] ✅ Cadastro e Login bem-sucedidos.");
                }
                return { 
                    success: true, 
                    message: responseData.message || "Cadastro realizado com sucesso!" 
                };
            } else {
                const message = responseData.message || responseData.email?.[0] || "Erro no cadastro. Verifique os dados.";
                console.error("[AUTH: REGISTER] ❌ FALHA NO CADASTRO (Status Não OK):", response.status, message);
                return { success: false, message };
            }

        } catch (error) {
            console.error('[AUTH: REGISTER] 🛑 Erro de rede ou CORS. Não foi possível conectar ao servidor.', error);
            return { success: false, message: "Não foi possível conectar ao servidor. Verifique a API." };
        } finally {
            setIsLoading(false); 
        }
    }, [setToken]);


    // -----------------------------------------------------------------
    // FUNÇÃO DE LOGIN (Mantida a implementação do utilizador)
    // -----------------------------------------------------------------

    const login = useCallback(async (credentials: { cpf: string, password: string }): Promise<{ success: boolean, message?: string }> => {
        setIsLoading(true); 
        const fullUrl = LOGIN_URL;
        
        console.log(`[AUTH: LOGIN] 📞 Tentativa de login. URL: ${fullUrl}`);

        try {
            const response = await fetch(fullUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(credentials),
            });
            
            console.log(`[AUTH: LOGIN] 🔄 Status HTTP: ${response.status}`);
            
            const data = await response.json();

            if (response.ok) {
                if (data.token && data.user) {
                    setToken(data.token);
                    setUser(data.user);
                    console.log("[AUTH: LOGIN] ✅ Login bem-sucedido. Token e Utilizador definidos.");
                    return { success: true };
                } else {
                    console.error("[AUTH: LOGIN] ❌ Resposta 200, mas dados incompletos.");
                    setToken(null); 
                    return { success: false, message: data.message || "Resposta da API incompleta. Faltam 'token' ou 'user'." };
                }
            } else {
                const message = data.message || data.cpf?.[0] || "Credenciais inválidas.";
                console.error("[AUTH: LOGIN] ❌ FALHA NO LOGIN (Status Não OK):", response.status, message);
                setToken(null); 
                return { success: false, message: message };
            }

        } catch (error) {
            console.error('[AUTH: LOGIN] 🛑 Erro de rede ou CORS. Não foi possível conectar ao servidor.', error);
            setToken(null); 
            return { success: false, message: "Não foi possível conectar ao servidor. Verifique a API." };
        } finally {
             setIsLoading(false); 
        }
    }, [setToken]);
    // -----------------------------------------------------------------

    // Efeito 1: Carregamento Inicial do Token e Definição do Estado
    useEffect(() => {
        const savedToken = localStorage.getItem(JWT_STORAGE_KEY); 
        if (savedToken) {
            setTokenState(savedToken);
            console.log("[AUTH] Token encontrado no LocalStorage.");
        } else {
            console.log("[AUTH] Nenhum token encontrado. Auth pronta (deslogada).");
            setTokenState(null);
            setUser(null);
            setIsLoading(false); 
        }
    }, []); 

    // Efeito 2: Obter Dados Reais do Utilizador (/api/me)
    useEffect(() => {
        let isCancelled = false;
        
        const fetchUserData = async (currentToken: string) => {
            if (!currentToken || isCancelled) {
                return;
            }

            console.log("[AUTH: REFRESH] 🛡️ Tentativa de validar token via /api/me.");
            setIsLoading(true);

            try {
                const response = await fetch(ME_URL, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${currentToken}`,
                    },
                });
                
                console.log(`[AUTH: REFRESH] 📡 Resposta /me. Status: ${response.status}`);

                if (response.ok) {
                    const data = await response.json();
                    if (!isCancelled) {
                        const validatedUser = data.user || data; 
                        setUser(validatedUser);
                        console.log("[AUTH: REFRESH] ✅ Token válido. Utilizador carregado.");
                    }
                } else {
                    console.error(`[AUTH: REFRESH] ❌ FALHA NA VALIDAÇÃO! HTTP STATUS: ${response.status}. A fazer logout.`);
                    
                    if (!isCancelled) {
                        localStorage.removeItem(JWT_STORAGE_KEY);
                        setUser(null);
                        setTokenState(null);
                    }
                }

            } catch (error) {
                console.error('[AUTH: REFRESH] 🛑 Erro de rede/API ao validar token. A fazer logout.', error);
                
                if (!isCancelled) {
                    localStorage.removeItem(JWT_STORAGE_KEY);
                    setUser(null);
                    setTokenState(null);
                }
            } finally {
                if (!isCancelled) {
                    setIsLoading(false); 
                }
            }
        };

        if (token) {
            fetchUserData(token); 
        } else if (!localStorage.getItem(JWT_STORAGE_KEY)) {
            // Se não houver token no estado nem no localStorage, paramos o loading
             setIsLoading(false);
        }

        return () => { isCancelled = true; };
    }, [token, setToken]); 

    const isAuthenticated = !!token && !!user;

    const authValue = useMemo(() => ({
        user, 
        token, 
        isAuthenticated, 
        isLoading, 
        setToken, 
        logout,
        login,
        register,
    }), [user, token, isAuthenticated, isLoading, setToken, logout, login, register]);

    return (
        <AuthContext.Provider value={authValue}>
            {children}
        </AuthContext.Provider>
    );
};

// =================================================================================
// 4. ÍCONES
// =================================================================================

const LoaderSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="animate-spin"><path d="M12 2v4"/><path d="m16.2 7.8 2.8-2.8"/><path d="M18 12h4"/><path d="m16.2 16.2 2.8 2.8"/><path d="M12 18v4"/><path d="m7.8 16.2-2.8 2.8"/><path d="M6 12H2"/><path d="m7.8 7.8-2.8-2.8"/></svg>
);

const UserPlusIcon = (props: React.SVGProps<SVGSVGElement>) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <line x1="17" x2="17" y1="10" y2="16" />
        <line x1="14" x2="20" y1="13" y2="13" />
    </svg>
);

const HomeSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
);

const LogOutSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
);

const LogInSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
);

const ServerCrashSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 10H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M6 14H4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2"/><path d="M10 2v2"/><path d="M14 2v2"/><path d="M10 20v2"/><path d="M14 20v2"/><path d="M12 10v4"/></svg>
);

const CheckCircleSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.05-8.6"/><path d="m9 11 3 3 7-7"/></svg>
);


// =================================================================================
// 5. COMPONENTES DE PÁGINA (Login e Cadastro - Integrados ao novo Contexto)
// =================================================================================

// MOCK DO ROUTER (Centralizado)
const useInternalRouter = () => {
    const [path, setPath] = useState('/signup'); // Começa na página de cadastro
    if (typeof window !== 'undefined') {
        useEffect(() => {
            // Sincroniza o estado com o hash da URL para navegação básica
            const handleHashChange = () => setPath(window.location.hash.slice(1) || '/signup');
            window.addEventListener('hashchange', handleHashChange);
            handleHashChange();
            return () => window.removeEventListener('hashchange', handleHashChange);
        }, []);
        // Atualiza o hash da URL para navegação
        const push = (newPath: string) => window.location.hash = newPath.startsWith('/') ? newPath.slice(1) : newPath;
        return { path, push };
    }
    return { path: '/signup', push: (path: string) => console.log('Mock push:', path) };
};

// Componente de Cadastro (SignupPage)
const SignupPage = () => {
    const { register, isAuthenticated, isLoading } = useAuth();
    const router = useInternalRouter();

    const [name, setName] = useState('');
    const [cpf, setCpf] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [birthday, setBirthday] = useState(''); 
    
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

        if (!name || !cpf || !email || !password || !phone || !birthday) {
            setError("Por favor, preencha todos os campos obrigatórios.");
            return;
        }

        setIsProcessing(true);

        const registerData = { 
            name, 
            cpf, 
            email, 
            phone,
            birthday,
            password, 
            password_confirmation: passwordConfirmation 
        };

        const result = await register(registerData);

        if (result.success) {
            // O AuthProvider já cuidou do login se tiver token
            setSuccessMessage(result.message || "Cadastro realizado! Redirecionando...");
            if (!isAuthenticated) { 
                // Se não logou automaticamente, redireciona para login.
                setTimeout(() => router.push('/login'), 2000); 
            }
        } else {
            setError(result.message || "Ocorreu um erro desconhecido durante o cadastro.");
        }
        
        setIsProcessing(false);
    };

    // Redirecionamento se já autenticado
    useEffect(() => {
        if (!isLoading && isAuthenticated) {
            router.push('/me');
        }
    }, [isLoading, isAuthenticated, router]);
    
    // Se estiver a carregar a autenticação inicial, mostra loading
    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
                <div className="text-center text-[#1a472a]"><LoaderSVG size={40} className="animate-spin mb-2" /> A Carregar Autenticação...</div>
            </div>
        );
    }

    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
            <div className="w-full max-w-lg bg-white p-8 rounded-xl shadow-2xl border border-gray-100">
                <div className="flex flex-col items-center mb-6">
                    <UserPlusIcon className="w-10 h-10 text-[#1a472a] mb-2" />
                    <h1 className="text-3xl font-extrabold text-[#1a472a]">Novo Cadastro de Aluno (Signup)</h1>
                    <p className="text-sm text-gray-500 mt-1">Endpoint: {REGISTER_URL}</p>
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
                                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#388e3c] focus:border-transparent transition text-gray-700"
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
                                <LoaderSVG className="w-5 h-5 mr-2 text-white" />
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

// Componente de Login (LoginPage)
const LoginPage = () => {
    const { login, isAuthenticated, isLoading } = useAuth();
    const router = useInternalRouter();
    
    // Credenciais de teste
    const [cpf, setCpf] = useState('12345678901'); 
    const [password, setPassword] = useState('password'); 
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Redirecionamento se já autenticado
    useEffect(() => {
        if (!isLoading && isAuthenticated) {
            router.push('/me');
        }
    }, [isLoading, isAuthenticated, router]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setMessage('');

        const result = await login({ cpf, password });
        
        if (!result.success) {
            setMessage(result.message || "Erro ao tentar fazer login.");
        } else {
            // O login bem-sucedido fará com que o useEffect redirecione
            setMessage("Login bem-sucedido! Redirecionando...");
        }
        
        setIsSubmitting(false);
    };

    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
                <div className="text-center text-red-700"><LoaderSVG size={40} className="animate-spin mb-2" /> A Carregar Autenticação...</div>
            </div>
        );
    }
    
    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
            <div className="p-8 border border-red-300 bg-red-50 rounded-xl shadow-lg w-full max-w-sm">
                <h2 className="text-3xl font-bold text-red-700 mb-6 text-center flex items-center justify-center">
                    <LogInSVG className="mr-3" size={30} /> Inicie Sessão
                </h2>
                
                <form onSubmit={handleSubmit} className="space-y-4">
                    <input
                        type="text"
                        placeholder="CPF"
                        value={cpf}
                        onChange={(e) => setCpf(e.target.value)}
                        className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                        required
                    />
                    <input
                        type="password"
                        placeholder="Senha"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                        required
                    />
                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="w-full py-3 px-6 rounded-lg font-semibold bg-red-500 hover:bg-red-600 text-white transition duration-150 shadow-md flex items-center justify-center disabled:opacity-50"
                    >
                        {isSubmitting ? <LoaderSVG className="animate-spin mr-2 h-5 w-5" /> : null}
                        {isSubmitting ? 'Aguarde...' : 'Entrar'}
                    </button>
                </form>

                {message && (
                    <div className={`p-3 rounded-lg mt-4 text-sm flex items-center justify-center ${message.includes('sucesso') ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}`}>
                        <ServerCrashSVG className="mr-2 h-5 w-5" /> {message}
                    </div>
                )}
                
                <p className="mt-6 text-center text-sm text-gray-500">
                    Ainda não tem conta? 
                    <a onClick={() => router.push('/signup')} className="text-red-600 hover:underline cursor-pointer font-medium ml-1">
                        Criar Conta
                    </a>
                </p>
            </div>
        </div>
    );
};

// Componente Protegido (ProtectedPage/Me)
const ProtectedPage = () => {
  const { user, logout, token, isLoading } = useAuth();
  const router = useInternalRouter();

  // Redireciona se não estiver autenticado (depois de o loading inicial terminar)
  useEffect(() => {
    if (!isLoading && !user) {
        router.push('/login');
    }
  }, [isLoading, user, router]);

  if (isLoading || !user) {
    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
            <div className="text-center text-green-700"><LoaderSVG size={40} className="animate-spin mb-2" /> A Validar Sessão...</div>
        </div>
    );
  }
  
  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
        <div className="text-center p-8 border border-green-300 bg-green-50 rounded-xl shadow-lg w-full max-w-xl">
            <h2 className="text-3xl font-bold text-green-700 flex items-center justify-center mb-4">
                <HomeSVG className="mr-3" size={30} /> Painel do Utilizador (ME)
            </h2>
            <p className="text-gray-700 mb-6">Bem-vinda(o), <span className="font-extrabold text-green-800">{user?.name || 'Utilizador'}</span>!</p>
            
            <div className="bg-white p-6 rounded-xl shadow-inner inline-block break-words text-left w-full">
                <p className="font-semibold text-gray-800 flex items-center mb-2 text-lg"><CheckCircleSVG className="text-green-500 mr-2 h-5 w-5"/> Detalhes do Login</p>
                <div className="space-y-1 text-sm text-gray-600 border-t pt-3">
                    <p><strong>Email:</strong> {user.email}</p>
                    <p><strong>CPF:</strong> {user.cpf}</p>
                    <p><strong>Data Nasc:</strong> {user.birthday}</p>
                    <p><strong>Perfil:</strong> <span className="capitalize font-medium text-blue-600">{user.role}</span></p>
                    <p className="truncate"><strong>Token (Amostra):</strong> {token?.substring(0, 40)}...</p>
                </div>
            </div>

            <button
                onClick={logout}
                className="mt-8 py-3 px-6 rounded-lg font-semibold bg-pink-500 hover:bg-pink-600 text-white transition duration-150 shadow-md flex items-center justify-center mx-auto"
            >
                <LogOutSVG className="mr-2" size={20} /> Fazer Logout
            </button>
        </div>
    </div>
  );
};

// =================================================================================
// 6. COMPONENTE RAIZ (App)
// =================================================================================

// O componente que usa o estado de autenticação para decidir o que mostrar.
const MainRouter = () => {
    const { path } = useInternalRouter();

    switch (path) {
        case '/login':
            return <LoginPage />;
        case '/me':
            return <ProtectedPage />;
        case '/signup':
        default:
            return <SignupPage />;
    }
};

// App principal que encapsula tudo no AuthProvider
export default function App() {
    return (
        // O estilo global para o Immersive Canvas, incluindo Tailwind CSS
        <div className="min-h-screen font-sans antialiased">
            <AuthProvider>
                <MainRouter />
            </AuthProvider>
        </div>
    );
}
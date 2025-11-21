'use client';

import React, { useState, useEffect, createContext, useContext, useCallback, useMemo } from 'react';

// === CONFIGURAÇÃO GLOBAL DA APLICAÇÃO ===
const BASE_URL = 'http://localhost:8000/api';
const REGISTER_URL = `${BASE_URL}/register`;
const LOGIN_URL = `${BASE_URL}/login`;
const ME_URL = `${BASE_URL}/me`;
const JWT_STORAGE_KEY = 'jwt_token';

// =================================================================================
// 1. Definições de Tipos e Interfaces
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
    register: (data: any) => Promise<{ success: boolean, message?: string }>;
}

interface IconProps extends React.SVGProps<SVGSVGElement> {
    size?: number;
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
    register: async () => ({ success: false }),
};

export const AuthContext = createContext<AuthContextType>(defaultAuthContext);
export const useAuth = () => useContext(AuthContext);

// =================================================================================
// 3. Provedor de Autenticação
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
    // REGISTRO
    // -----------------------------------------------------------------

    const register = useCallback(async (data: any): Promise<{ success: boolean, message?: string }> => {
        console.log(`[AUTH: REGISTER] Tentativa de cadastro. URL: ${REGISTER_URL}`);
        setIsLoading(true);

        try {
            const response = await fetch(REGISTER_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });

            const responseData = await response.json();

            if (response.ok) {
                if (responseData.token && responseData.user) {
                    setToken(responseData.token);
                    setUser(responseData.user);
                }
                return { success: true, message: responseData.message || "Cadastro realizado com sucesso!" };
            } else {
                const message = responseData.message || responseData.email?.[0] || "Erro no cadastro.";
                return { success: false, message };
            }

        } catch (error) {
            console.error('[AUTH: REGISTER] Erro de rede.', error);
            return { success: false, message: "Erro ao conectar ao servidor." };
        } finally {
            setIsLoading(false);
        }
    }, [setToken]);

    // -----------------------------------------------------------------
    // LOGIN
    // -----------------------------------------------------------------

    const login = useCallback(async (credentials: { cpf: string, password: string }): Promise<{ success: boolean, message?: string }> => {
        setIsLoading(true);

        try {
            const response = await fetch(LOGIN_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(credentials),
            });

            const data = await response.json();

            if (response.ok && data.token && data.user) {
                setToken(data.token);
                setUser(data.user);
                return { success: true };
            } else {
                return { success: false, message: data.message || "Credenciais inválidas." };
            }

        } catch (error) {
            console.error('[AUTH: LOGIN] Erro de rede.', error);
            return { success: false, message: "Erro ao conectar ao servidor." };
        } finally {
            setIsLoading(false);
        }
    }, [setToken]);

    // -----------------------------------------------------------------
    // Carrega token do localStorage uma vez
    // -----------------------------------------------------------------

    useEffect(() => {
        const savedToken = localStorage.getItem(JWT_STORAGE_KEY);

        if (savedToken) {
            setTokenState(savedToken);
        }
        setIsLoading(false);
    }, []);

    // -----------------------------------------------------------------
    // Busca os dados do usuário com /me
    // -----------------------------------------------------------------

    useEffect(() => {
        const current = localStorage.getItem(JWT_STORAGE_KEY);
        if (!current) return;

        let cancel = false;

        const load = async () => {
            setIsLoading(true);
            try {
                const response = await fetch(ME_URL, {
                    method: 'GET',
                    headers: { 'Authorization': `Bearer ${current}` },
                });

                if (!cancel && response.ok) {
                    const data = await response.json();
                    setUser(data.user || data);
                } else if (!cancel) {
                    logout();
                }
            } catch (error) {
                if (!cancel) logout();
            } finally {
                if (!cancel) setIsLoading(false);
            }
        };

        load();
        return () => { cancel = true };
    }, [token, logout]);

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
    <svg {...props} width={size} height={size} className="animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2v4"/><path d="m16.2 7.8 2.8-2.8"/><path d="M18 12h4"/><path d="m16.2 16.2 2.8 2.8"/><path d="M12 18v4"/><path d="m7.8 16.2-2.8 2.8"/><path d="M6 12H2"/><path d="m7.8 7.8-2.8-2.8"/></svg>
);

// -------------------------------------------------------------------------------
// ROUTER SIMPLES
// -------------------------------------------------------------------------------

const useInternalRouter = () => {
    const [path, setPath] = useState('/signup');

    if (typeof window !== 'undefined') {
        useEffect(() => {
            const handle = () => setPath(window.location.hash.slice(1) || '/signup');
            window.addEventListener('hashchange', handle);
            handle();
            return () => window.removeEventListener('hashchange', handle);
        }, []);

        const push = (newPath: string) => window.location.hash = newPath.replace('/', '');
        return { path, push };
    }

    return { path: '/signup', push: () => {} };
};

// =================================================================================
// SIGNUP PAGE
// =================================================================================

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
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const [isProcessing, setIsProcessing] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setSuccessMessage(null);

        if (password !== passwordConfirmation) {
            setError("As senhas não coincidem.");
            return;
        }

        const result = await register({
            name, cpf, email, phone, birthday,
            password,
            password_confirmation: passwordConfirmation
        });

        if (result.success) {
            setSuccessMessage(result.message ?? null);
            setTimeout(() => router.push('/me'), 1000);
        } else {
            setError(result.message ?? null);
        }
    };

    useEffect(() => {
        if (!isLoading && isAuthenticated) {
            router.push('/me');
        }
    }, [isAuthenticated, isLoading, router]);

    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <LoaderSVG size={40} /> Carregando...
            </div>
        );
    }

    return (
        <div className="flex items-center justify-center min-h-screen bg-gray-100 p-4">
            <div className="w-full max-w-lg bg-white p-8 rounded-xl shadow-xl">
                <h1 className="text-3xl font-bold text-green-900 mb-3">Cadastro de Aluno</h1>
                <p className="text-xs text-gray-500 mb-4">{REGISTER_URL}</p>

                {error && (
                    <div className="p-2 text-sm bg-red-100 text-red-800 rounded mb-3">{error}</div>
                )}

                {successMessage && (
                    <div className="p-2 text-sm bg-green-100 text-green-800 rounded mb-3">{successMessage}</div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="text" placeholder="Nome"
                            value={name} onChange={e => setName(e.target.value)}
                            className="input" required />

                        <input type="email" placeholder="Email"
                            value={email} onChange={e => setEmail(e.target.value)}
                            className="input" required />
                    </div>

                    <div className="grid grid-cols-3 gap-3">
                        <input type="text" placeholder="CPF"
                            value={cpf} onChange={e => setCpf(e.target.value)}
                            className="input" required />

                        <input type="text" placeholder="Telefone"
                            value={phone} onChange={e => setPhone(e.target.value)}
                            className="input" required />

                        <input type="date" value={birthday}
                            onChange={e => setBirthday(e.target.value)}
                            className="input" required />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="password" placeholder="Senha"
                            value={password} onChange={e => setPassword(e.target.value)}
                            className="input" required />

                        <input type="password" placeholder="Confirmar senha"
                            value={passwordConfirmation} onChange={e => setPasswordConfirmation(e.target.value)}
                            className="input" required />
                    </div>

                    <button disabled={isProcessing}
                        className="w-full py-3 bg-green-700 text-white font-bold rounded-lg hover:bg-green-800 transition">
                        {isProcessing ? "Aguarde..." : "Cadastrar"}
                    </button>
                </form>
            </div>
        </div>
    );
};

// =====================================================================
// EXPORT PARA USO
// =====================================================================

export default SignupPage;

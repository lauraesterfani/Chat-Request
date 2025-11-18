'use client';

import React, { useState, useEffect, createContext, useContext, useCallback } from 'react';
// Ícones Lucide substituídos por SVG inline e tipados.

// === CONFIGURAÇÃO CORRETA DA URL BASE (PORTA 8002) ===
const BASE_URL = 'http://localhost:8002/api'; 
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
    register: (data: any) => Promise<boolean>;
}

// Interface para tipar os componentes SVG, resolvendo os novos erros de tipagem.
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
    register: async () => false,
};

export const AuthContext = createContext<AuthContextType>(defaultAuthContext);
export const useAuth = () => useContext(AuthContext); 

// =================================================================================
// 3. Provedor de Autenticação (AuthProvider)
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
    // FUNÇÕES DE SERVIÇO
    // -----------------------------------------------------------------
    
    const login = async (credentials: { cpf: string, password: string }): Promise<{ success: boolean, message?: string }> => {
        setIsLoading(true); 
        const fullUrl = `${BASE_URL}/login`;
        
        console.log(`[AUTH: LOGIN] 📞 Tentativa de login. URL: ${fullUrl}`);

        try {
            const response = await fetch(fullUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
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
    };

    const register = async (data: any) => {
        console.log("REGISTER: Chamada de API simulada (implementar)");
        return false;
    };
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
                const response = await fetch(`${BASE_URL}/me`, {
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
        }

        return () => { isCancelled = true; };
    }, [token]); 

    const isAuthenticated = !!token && !!user;

    return (
        <AuthContext.Provider value={{ 
            user, 
            token, 
            isAuthenticated, 
            isLoading, 
            setToken, 
            logout,
            login,
            register,
        }}>
            {children}
        </AuthContext.Provider>
    );
};

// =================================================================================
// 4. Componentes de Demonstração (ME e Login)
// =================================================================================

// Componente SVG para substituir Lucide Loader
const LoaderSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.8-2.8"/><path d="M18 12h4"/><path d="m16.2 16.2 2.8 2.8"/><path d="M12 18v4"/><path d="m7.8 16.2-2.8 2.8"/><path d="M6 12H2"/><path d="m7.8 7.8-2.8-2.8"/></svg>
);

// Componente SVG para substituir Lucide Home
const HomeSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
);

// Componente SVG para substituir Lucide LogOut
const LogOutSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
);

// Componente SVG para substituir Lucide LogIn
const LogInSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
);

// Componente SVG para substituir Lucide ServerCrash
const ServerCrashSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M6 10H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M6 14H4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2"/><path d="M10 2v2"/><path d="M14 2v2"/><path d="M10 20v2"/><path d="M14 20v2"/><path d="M12 10v4"/></svg>
);

// Componente SVG para substituir Lucide CheckCircle
const CheckCircleSVG = ({ size = 24, ...props }: IconProps) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.05-8.6"/><path d="m9 11 3 3 7-7"/></svg>
);


// Simulação da sua Página Protegida (o 'Me')
const ProtectedPage = () => {
  const { user, logout, token } = useAuth();
  
  return (
    <div className="text-center p-8 border border-green-300 bg-green-50 rounded-xl shadow-lg">
      <h2 className="text-3xl font-bold text-green-700 flex items-center justify-center mb-4">
        <HomeSVG className="mr-3" size={30} /> Página Protegida (ME)
      </h2>
      <p className="text-gray-700 mb-4">Bem-vinda(o), **{user?.name || 'Utilizador'}**!</p>
      
      <div className="bg-white p-4 rounded-lg shadow-inner inline-block break-words text-left">
        <p className="font-semibold text-gray-800 flex items-center mb-1"><CheckCircleSVG className="text-green-500 mr-2 h-4 w-4"/> Login Válido</p>
        <p className="font-mono text-sm text-gray-600 truncate">Token: {token?.substring(0, 30)}...</p>
        <p className="font-mono text-sm text-gray-600">Email: {user?.email}</p>
      </div>

      <button
        onClick={logout}
        className="mt-8 py-3 px-6 rounded-lg font-semibold bg-pink-500 hover:bg-pink-600 text-white transition duration-150 shadow-md flex items-center justify-center mx-auto"
      >
        <LogOutSVG className="mr-2" size={20} /> Fazer Logout
      </button>
    </div>
  );
};

// Simulação da sua Página de Login
const LoginPage = () => {
    const { login } = useAuth();
    // Credenciais de teste
    const [cpf, setCpf] = useState('12345678901'); 
    const [password, setPassword] = useState('password'); 
    const [message, setMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // CORREÇÃO ANTERIOR: Adicionando o tipo React.FormEvent para o parâmetro 'e'
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setMessage('');

        const result = await login({ cpf, password });
        
        if (!result.success) {
            setMessage(result.message || "Erro ao tentar fazer login.");
        }
        
        setIsSubmitting(false);
    };

    return (
        <div className="p-8 border border-red-300 bg-red-50 rounded-xl shadow-lg w-full max-w-sm mx-auto">
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
                <div className="bg-red-200 text-red-800 p-3 rounded-lg mt-4 text-sm flex items-center justify-center">
                    <ServerCrashSVG className="mr-2 h-5 w-5" /> {message}
                </div>
            )}
            
            <p className="text-center text-xs text-gray-500 mt-4">
                Tente fazer logout (se logado) e depois tente logar com as credenciais.
            </p>
        </div>
    );
};


// =================================================================================
// 5. Componente Raiz (A Aplicação que usa o Contexto)
// =================================================================================



// O componente que usa o estado de autenticação para decidir o que mostrar.
const MainRouter = () => {
    const { isAuthenticated, isLoading } = useAuth(); 

    // LÓGICA DE

}
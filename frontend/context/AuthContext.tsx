'use client'

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useRouter } from 'next/navigation';

// 1. Define o formato dos dados do usuário (baseado no seu backend)
interface User {
  id: string;
  name: string;
  email: string;
  cpf: string;
  phone: string;
  role: 'admin' | 'student' | 'staff'; //
  birthday: string;
}

// 2. Define o que o nosso Contexto vai fornecer
interface AuthContextType {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  login: (token: string, user: User) => void;
  logout: () => void;
}

// 3. Cria o Contexto
const AuthContext = createContext<AuthContextType | undefined>(undefined);

// 4. Cria o "Provedor" (o componente que vai gerenciar o estado)
export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true); // Começa carregando
  const router = useRouter();

  // 5. Efeito que roda UMA VEZ quando o app carrega
  useEffect(() => {
    // Tenta pegar o token e usuário do localStorage
    const storedToken = localStorage.getItem('auth_token');
    const storedUser = localStorage.getItem('auth_user');

    if (storedToken && storedUser) {
      setToken(storedToken);
      setUser(JSON.parse(storedUser));
      // AQUI você deveria fazer um fetch para /api/me para re-validar o token
      // Mas por enquanto, vamos confiar no localStorage
    }
    setIsLoading(false); // Termina de carregar
  }, []);

  // 6. Função de Login
  const login = (newToken: string, newUser: User) => {
    // Salva no estado
    setToken(newToken);
    setUser(newUser);
    // Salva no localStorage para persistir
    localStorage.setItem('auth_token', newToken);
    localStorage.setItem('auth_user', JSON.stringify(newUser));

    // **A MÁGICA DO REDIRECIONAMENTO**
    // Redireciona baseado na role do backend
    if (newUser.role === 'admin' || newUser.role === 'staff') {
      router.push('/dashboard'); // Rota do Admin
    } else {
      router.push('/chat'); // Rota do Aluno
    }
  };

  // 7. Função de Logout
  const logout = () => {
    // Limpa o estado
    setUser(null);
    setToken(null);
    // Limpa o localStorage
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    // Manda para o login
    router.push('/');
    // (Opcional: chamar /api/logout do seu backend)
  };

  return (
    <AuthContext.Provider value={{ user, token, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

// 8. Hook customizado para facilitar o uso
export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth deve ser usado dentro de um AuthProvider');
  }
  return context;
}
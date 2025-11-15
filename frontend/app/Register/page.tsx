'use client'

import { useState } from 'react';
import Image from 'next/image';
import Link from 'next/link';
import { useRouter } from 'next/navigation'; // Para redirecionar após o sucesso

// Interface para os erros de validação do Laravel
interface ValidationErrors {
  [key: string]: string[];
}

export default function RegisterPage() {
  const router = useRouter(); // Hook para navegação

  // Estados para todos os campos do formulário
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [cpf, setCpf] = useState('');
  const [phone, setPhone] = useState('');
  const [birthday, setBirthday] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');

  // Estados para feedback
  const [errors, setErrors] = useState<ValidationErrors | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  // Função de envio do formulário
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setErrors(null);
    setSuccess(null);

    // Seu backend espera 'password_confirmation'
    if (password !== passwordConfirmation) {
      setErrors({ password: ['As senhas não conferem.'] });
      setIsLoading(false);
      return;
    }

    // Seu backend espera CPF e Telefone com 11 dígitos
    const cpfLimpo = cpf.replace(/\D/g, '');
    const phoneLimpo = phone.replace(/\D/g, '');

    try {
      const response = await fetch('http://localhost:8000/api/register', { //
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          name: name,
          email: email,
          cpf: cpfLimpo,       // Envia o CPF limpo
          phone: phoneLimpo,   // Envia o telefone limpo
          birthday: birthday,  // Formato YYYY-MM-DD
          password: password,
          password_confirmation: passwordConfirmation,
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        // Se for erro de validação (422), 'data.errors' existirá
        if (response.status === 422 && data.errors) {
          setErrors(data.errors);
        } else {
          throw new Error(data.message || 'Erro ao criar conta.');
        }
      } else {
        // Sucesso!
        setSuccess('Conta criada com sucesso! Redirecionando para o login...');
        // Redireciona para o login após 2 segundos
        setTimeout(() => {
          router.push('/'); // Redireciona para a home (login)
        }, 2000);
      }
    } catch (err: any) {
      setErrors({ general: [err.message || 'Não foi possível conectar ao servidor.'] });
    } finally {
      setIsLoading(false);
    }
  };

  // Função auxiliar para mostrar erros de validação
  const getError = (field: string) => {
    if (errors && errors[field]) {
      return (
        <p className="text-xs text-red-600 mt-1">{errors[field][0]}</p>
      );
    }
    return null;
  };

  return (
    <main className="flex min-h-screen">
      
      {/* ===== COLUNA DA ESQUERDA (VERDE) ===== */}
      <div className="w-1/2 bg-[#1a472a] flex flex-col items-center justify-center p-12 text-white text-center">
        <Image
          src="/mascote.png" // Da pasta 'frontend/public'
          alt="Mascote Chat Request"
          width={200}
          height={200}
          priority
        />
        <h1 className="text-4xl font-bold mt-6">
          Crie sua Conta no Chat Request!
        </h1>
        <p className="text-lg mt-4 max-w-md">
          Preencha seus dados para começar a gerenciar seus requerimentos.
        </p>
      </div>

      {/* ===== COLUNA DA DIREITA (FORMULÁRIO) ===== */}
      <div className="w-1/2 bg-gray-100 flex items-center justify-center p-12">
        <div className="bg-white p-10 rounded-lg shadow-2xl w-full max-w-md">
          <h2 className="text-3xl font-bold text-center text-[#1a472a]">
            Criar Conta
          </h2>
          
          <form onSubmit={handleSubmit} className="space-y-4 mt-8">
            {/* --- Campo Nome --- */}
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-gray-700">Nome Completo</label>
              <input
                id="name" type="text" value={name}
                onChange={(e) => setName(e.target.value)}
                required
                className="w-full mt-1 px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
              />
              {getError('name')}
            </div>

            {/* --- Campo Email --- */}
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700">Email</label>
              <input
                id="email" type="email" value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                className="w-full mt-1 px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
              />
              {getError('email')}
            </div>

            {/* --- Linha CPF e Telefone --- */}
            <div className="flex space-x-4">
              <div className="w-1/2">
                <label htmlFor="cpf" className="block text-sm font-medium text-gray-700">CPF</label>
                <input
                  id="cpf" type="text" value={cpf}
                  onChange={(e) => setCpf(e.target.value)}
                  placeholder="000.000.000-00" required
                  className="w-full mt-1 px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
                />
                {getError('cpf')}
              </div>
              <div className="w-1/2">
                <label htmlFor="phone" className="block text-sm font-medium text-gray-700">Telefone</label>
                <input
                  id="phone" type="tel" value={phone}
                  onChange={(e) => setPhone(e.target.value)}
                  placeholder="(00) 00000-0000" required
                  className="w-full mt-1 px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
                />
                {getError('phone')}
              </div>
            </div>

            {/* --- Campo Data de Nascimento --- */}
            <div>
              <label htmlFor="birthday" className="block text-sm font-medium text-gray-700">Data de Nascimento</label>
              <input
                id="birthday" type="date" value={birthday}
                onChange={(e) => setBirthday(e.target.value)}
                required
                className="w-full mt-1 px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
              />
              {getError('birthday')}
            </div>

            {/* --- Campo Senha --- */}
            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700">Senha</label>
              <input
                id="password" type="password" value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                className="w-full mt-1 px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
              />
              {getError('password')}
            </div>

            {/* --- Campo Confirmar Senha --- */}
            <div>
              <label htmlFor="passwordConfirmation" className="block text-sm font-medium text-gray-700">Confirmar Senha</label>
              <input
                id="passwordConfirmation" type="password" value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                required
                className="w-full mt-1 px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-700 focus:border-green-700"
              />
            </div>
            
            {/* --- Mensagens de Status --- */}
            {errors?.general && (
              <p className="text-sm text-red-600 text-center">{errors.general[0]}</p>
            )}
            {success && (
              <p className="text-sm text-green-600 text-center">{success}</p>
            )}

            {/* --- Botão Criar Conta --- */}
            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-3 px-4 bg-[#1a472a] text-white font-semibold rounded-md shadow-lg hover:bg-green-800 transition duration-300 disabled:bg-gray-400"
            >
              {isLoading ? 'Criando...' : 'Criar Conta'}
            </button>
          </form>

          {/* --- Link Voltar --- */}
          <p className="text-center text-gray-600 mt-6">
            Já tem uma conta?{' '}
            <Link href="/" className="text-green-700 hover:underline font-medium">
              Faça login
            </Link>
          </p>
        </div>
      </div>
    </main>
  );
}
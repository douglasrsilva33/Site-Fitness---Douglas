
const precos = {
    Basico: 89.90,
    Plus: 129.90,
    Premium: 179.90
};

const nomesPlanos = {
    Basico: "Básico",
    Plus: "Plus",
    Premium: "Premium"
};

function calcularIdade(dataNascimento, dataReferencia = new Date()) {
    const nascimento = new Date(`${dataNascimento}T12:00:00`);

    if (Number.isNaN(nascimento.getTime())) {
        return NaN;
    }

    let idade = dataReferencia.getFullYear() - nascimento.getFullYear();

    const aindaNaoFezAniversario =
        dataReferencia.getMonth() < nascimento.getMonth() ||
        (
            dataReferencia.getMonth() === nascimento.getMonth() &&
            dataReferencia.getDate() < nascimento.getDate()
        );

    if (aindaNaoFezAniversario) {
        idade--;
    }

    return idade;
}

function formatarMoeda(valor) {
    return valor.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL"
    });
}

function obterDesconto(idade, pagamento) {
    // Aplica somente o maior percentual, sem acumular descontos.
    if (idade >= 60) {
        return 15;
    }

    if (pagamento === "PIX") {
        return 10;
    }

    return 0;
}

document.addEventListener("DOMContentLoaded", () => {
    const formulario = document.getElementById("formMatricula");

    if (!formulario) {
        return;
    }

    const nascimento = document.getElementById("nascimento");
    const dataMatricula = document.getElementById("data");
    const plano = document.getElementById("plano");
    const pagamento = document.getElementById("pagamento");
    const previa = document.getElementById("previa");

    // Define a data atual no formulário.
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, "0");
    const dia = String(hoje.getDate()).padStart(2, "0");
    const hojeISO = `${ano}-${mes}-${dia}`;

    dataMatricula.value = hojeISO;
    dataMatricula.max = hojeISO;
    nascimento.max = hojeISO;

    function atualizarPrevia() {
        if (!plano.value || !pagamento.value || !nascimento.value) {
            previa.textContent =
                "Informe a data de nascimento e selecione o plano e o pagamento.";
            return;
        }

        const idade = calcularIdade(nascimento.value);

        if (!Number.isFinite(idade) || idade < 0) {
            previa.textContent = "Informe uma data de nascimento válida.";
            return;
        }

        if (idade < 18) {
            previa.textContent =
                "Matrícula não permitida: o aluno deve ter pelo menos 18 anos.";
            return;
        }

        const original = precos[plano.value];
        const desconto = obterDesconto(idade, pagamento.value);
        const valorDesconto = Math.round(original * desconto) / 100;
        const final = Math.round((original - valorDesconto) * 100) / 100;

        previa.replaceChildren();

        const linhas = [
            `Plano: ${nomesPlanos[plano.value]}`,
            `Valor original: ${formatarMoeda(original)}`,
            `Desconto aplicado: ${desconto}% (${formatarMoeda(valorDesconto)})`,
            `Valor final: ${formatarMoeda(final)}`
        ];

        linhas.forEach((linha) => {
            const paragrafo = document.createElement("p");
            paragrafo.textContent = linha;
            previa.appendChild(paragrafo);
        });
    }

    [nascimento, plano, pagamento].forEach((campo) => {
        campo.addEventListener("change", atualizarPrevia);
    });

    formulario.addEventListener("submit", (evento) => {
        const idade = calcularIdade(nascimento.value);

        if (!Number.isFinite(idade) || idade < 18) {
            evento.preventDefault();
            alert("A matrícula é permitida somente para pessoas com 18 anos ou mais.");
            nascimento.focus();
            return;
        }

        if (new Date(`${dataMatricula.value}T12:00:00`) > new Date()) {
            evento.preventDefault();
            alert("A data da matrícula não pode ser futura.");
            dataMatricula.focus();
        }
    });
});

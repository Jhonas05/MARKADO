export const student = {
  name: 'Mia Santos',
  firstName: 'Mia',
  studentNumber: 'STU-2026-0142',
  initials: 'MS',
}

export const quiz = {
  code: 'BIO-104',
  title: 'General Biology',
  subtitle: 'Unit 3 · Ecosystems checkpoint',
  instructor: 'Prof. Elena Reyes',
  schedule: '22 Oct 2026 · 4:00 PM',
  timezone: 'Asia/Manila',
  durationMinutes: 25,
  points: 5,
}

export const questions = [
  {
    id: 'q1',
    topic: 'Ecology fundamentals',
    prompt: 'Which statement best describes an ecosystem?',
    choices: [
      {
        id: 'q1-a',
        text: 'A group of organisms from the same species in one area',
      },
      {
        id: 'q1-b',
        text: 'Living organisms interacting with one another and their physical environment',
      },
      {
        id: 'q1-c',
        text: 'The physical features of a habitat without its organisms',
      },
      {
        id: 'q1-d',
        text: 'Every organism found within a single climate zone',
      },
    ],
    correctChoiceId: 'q1-b',
    explanation:
      'An ecosystem includes both the living community and the nonliving environment they interact with.',
  },
  {
    id: 'q2',
    topic: 'Energy flow',
    prompt: 'What is the primary source of energy for most ecosystems?',
    choices: [
      { id: 'q2-a', text: 'Minerals in the soil' },
      { id: 'q2-b', text: 'Heat from decomposers' },
      { id: 'q2-c', text: 'Sunlight captured by producers' },
      { id: 'q2-d', text: 'Water stored in plants' },
    ],
    correctChoiceId: 'q2-c',
    explanation:
      'Producers capture sunlight through photosynthesis, beginning the main energy pathway in most ecosystems.',
  },
  {
    id: 'q3',
    topic: 'Food relationships',
    prompt: 'In a food web, arrows usually point in which direction?',
    choices: [
      { id: 'q3-a', text: 'From the consumer toward its food source' },
      { id: 'q3-b', text: 'From the larger organism toward the smaller organism' },
      { id: 'q3-c', text: 'From the energy source toward the organism receiving energy' },
      { id: 'q3-d', text: 'From decomposers toward all producers' },
    ],
    correctChoiceId: 'q3-c',
    explanation:
      'Food-web arrows show the transfer of energy from the organism being consumed to the consumer.',
  },
  {
    id: 'q4',
    topic: 'Population change',
    prompt: 'Which factor is density-dependent?',
    choices: [
      { id: 'q4-a', text: 'A volcanic eruption' },
      { id: 'q4-b', text: 'Competition for limited food' },
      { id: 'q4-c', text: 'A sudden typhoon' },
      { id: 'q4-d', text: 'Seasonal temperature change' },
    ],
    correctChoiceId: 'q4-b',
    explanation:
      'Competition becomes more influential as population density rises, making it density-dependent.',
  },
  {
    id: 'q5',
    topic: 'Nutrient cycles',
    prompt: 'Why are decomposers essential to nutrient cycling?',
    choices: [
      { id: 'q5-a', text: 'They create new energy for producers' },
      { id: 'q5-b', text: 'They remove all carbon from the atmosphere' },
      { id: 'q5-c', text: 'They return nutrients from dead matter to the environment' },
      { id: 'q5-d', text: 'They prevent consumers from using energy' },
    ],
    correctChoiceId: 'q5-c',
    explanation:
      'Decomposers break down dead organisms and waste, returning reusable nutrients to soil and water.',
  },
]

export const recentResults = [
  {
    subject: 'Cell Biology',
    detail: 'Unit 2 checkpoint',
    score: '18 / 20',
    percent: 90,
    date: '18 Oct',
  },
  {
    subject: 'Earth Science',
    detail: 'Plate tectonics',
    score: '16 / 20',
    percent: 80,
    date: '14 Oct',
  },
  {
    subject: 'Research Methods',
    detail: 'Variables and sampling',
    score: '19 / 20',
    percent: 95,
    date: '09 Oct',
  },
]


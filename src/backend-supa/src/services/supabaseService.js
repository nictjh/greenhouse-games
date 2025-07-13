import {createClient} from "@supabase/supabase-js";
import dotenv from 'dotenv';

dotenv.config();

// Check
// console.log('SUPABASE_URL:', process.env.SUPABASE_URL);
// console.log('SUPABASE_SERVICE_ROLE_KEY:', process.env.SUPABASE_SERVICE_ROLE_KEY);

if (!process.env.SUPABASE_URL || !process.env.SUPABASE_SERVICE_ROLE_KEY) {
    console.error('Supabase credentials missing. Check your .env file!');
    process.exit(1);
}

const database = createClient(
    process.env.SUPABASE_URL,
    process.env.SUPABASE_SERVICE_ROLE_KEY
)

export default database;

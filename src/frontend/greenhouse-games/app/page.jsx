'use client'

import { ChakraProvider } from "@chakra-ui/react";
import BoostCard from "./components/boostCard";
import Carousel from "./components/carousel";

export default function Home() {
  return (
    <div>
      <Carousel/>
      <BoostCard/>
    </div>
  );
}

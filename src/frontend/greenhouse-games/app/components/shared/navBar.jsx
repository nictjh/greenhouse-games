'use client'
import { Image, Box, Flex, Input, Button, Text, HStack, IconButton } from "@chakra-ui/react";
import { FiSearch, FiShoppingCart } from "react-icons/fi";
import ScrollableCategories from "./scrollableCategories";

const Navbar = () => {
  return (
    <Box as="nav" w="full" borderBottom="1px" borderColor="gray.200" bg="#D8DFCD">
      {/* Top Section - Logo, Search, Auth, Cart */}
      <Flex 
        justify="space-evenly" 
        align="center" 
        px={6} 
        maxW="container.xl" 
        mx="auto"
      >
        {/* Company Logo - Left aligned */}
        <Box flex="1">
          <Image 
            src="/GHG_icons/GHG-icon.png"
            alt="EduGames Logo"
            h="6vw"
            w="auto"
          />
        </Box>

        {/* Center-aligned Search Bar */}
        <Box flex="2" px={4} position="relative">
          <Input 
            placeholder="Search games..." 
            borderRadius="full"
            borderColor="gray.700"
            _hover={{ borderColor: "gray.400" }}
            _focus={{ borderColor: "green.500", boxShadow: "none" }}
            pl={4}
            pr={10}
          />

          <IconButton
            aria-label="Search games"
            position="absolute"
            right={6}
            top="50%"
            transform="translateY(-50%)"
            bg="#224750"
            color="white"
            borderRadius="full"
            size="xs"
            fontSize="16px"
            _hover={{ bg: "#2d5a65" }}
          >
            <FiSearch />
          </IconButton>
        </Box>

        {/* Auth Buttons and Cart */}
        <Flex flex="1" justify="flex-end" align="center" gap={2}>
          <Button 
            variant="ghost" 
            colorScheme="gray" 
            size="sm"
            color="#1b4a26"
            _hover={{ bg: 'gray.200' }}
          >
            Log In
          </Button>
          <Button 
            variant="ghost" 
            colorScheme="green" 
            size="sm"
            color="#1b4a26" 
            _hover={{ bg: 'gray.200' }}
          >
            Sign Up
          </Button>
          {/* Cart Icon */}
          <IconButton
            aria-label="Shopping Cart"
            variant="ghost"
            colorScheme="gray"
            color="#1b4a26"
            size="md"
            fontSize="16px"
            isRound
            _hover={{ bg: 'green.200' }}
          >
            <FiShoppingCart />
          </IconButton>
        </Flex>
      </Flex>

      {/* Bottom Section */}
      <Box 
        w="full" 
        overflowX="auto" 
        py={1} 
        px={6}
        bg="#D8DFCD"
        css={{
          '&::-webkit-scrollbar': {
            height: '4px',
          },
          '&::-webkit-scrollbar-track': {
            background: 'transparent',
          },
          '&::-webkit-scrollbar-thumb': {
            background: 'gray.300',
            borderRadius: '2px',
          },
        }}
      >
        <HStack spacing={4} minW="max-content" justify="center">
          {['Math Games', 'Coding Games', 'Language Games', 'Memorization Games', 'Science Games', 'Art Games', 'SEL Games'].map((category) => (
            <Button 
              key={category}
              variant="ghost"
              colorScheme="gray"
              size="sm"
              px={4}
              borderRadius="full"
              _hover={{ bg: 'gray.200' }}
              _active={{ bg: 'gray.300' }}
              color="black"
            >
              {category}
            </Button>
          ))}
        </HStack>
      </Box>
    </Box>
  );
};

export default Navbar;
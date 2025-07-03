import { Box, Flex, Input, Button, Text, HStack, IconButton } from "@chakra-ui/react";

const Navbar = () => {
  return (
    <Box as="nav" w="full" borderBottom="1px" borderColor="gray.200" bg="white">
      {/* Top Section - Logo, Search, Auth, Cart */}
      <Flex 
        justify="space-between" 
        align="center" 
        px={6} 
        py={4}
        maxW="container.xl" 
        mx="auto"
      >
        {/* Company Logo - Left aligned */}
        <Box flex="1">
          <Text fontSize="xl" fontWeight="bold" color="green.600">
            EduGames
          </Text>
        </Box>

        {/* Center-aligned Search Bar */}
        <Box flex="2" px={4} position="relative">
          <Input 
            placeholder="Search games..." 
            borderRadius="full"
            borderColor="gray.300"
            _hover={{ borderColor: "gray.400" }}
            _focus={{ borderColor: "green.500", boxShadow: "none" }}
            pl={4}
            pr={10} // Make room for the search icon
          />
          {/* Search icon positioned absolutely */}
          <Box 
            position="absolute" 
            right={8} 
            top="50%" 
            transform="translateY(-50%)"
            color="green.500" 
            cursor="pointer"
          >
            🔍
          </Box>
        </Box>

        {/* Auth Buttons and Cart - Right aligned */}
        <Flex flex="1" justify="flex-end" align="center" gap={4}>
          <Button variant="ghost" colorScheme="gray" size="sm">
            Log In
          </Button>
          <Button colorScheme="green" size="sm">
            Sign Up
          </Button>
          {/* Cart Icon */}
          <IconButton
            aria-label="Shopping Cart"
            icon={<Box as="span">🛒</Box>}
            variant="ghost"
            colorScheme="gray"
            size="sm"
            isRound
          />
        </Flex>
      </Flex>

      {/* Bottom Section - Game Categories Slider */}
      <Box 
        w="full" 
        overflowX="auto" 
        py={2} 
        px={6}
        bg="gray.50"
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
